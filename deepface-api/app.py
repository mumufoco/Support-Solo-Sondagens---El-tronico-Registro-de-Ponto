"""
DeepFace API - Facial Recognition Service
Sistema de Ponto Eletrônico Brasileiro
"""

import os
import base64
import logging
import hashlib
from datetime import datetime
from io import BytesIO

from flask import Flask, request, jsonify
from flask_cors import CORS
from flask_limiter import Limiter
from flask_limiter.util import get_remote_address
from PIL import Image
from deepface import DeepFace
import numpy as np

from config import config, Config

# Initialize Flask app
app = Flask(__name__)

# Load configuration
env = os.getenv('FLASK_ENV', 'development')
app.config.from_object(config[env])

# Setup CORS
CORS(app, resources={r"/*": {"origins": Config.CORS_ORIGINS}})

# Setup rate limiting
limiter = Limiter(
    app=app,
    key_func=get_remote_address,
    default_limits=[Config.RATELIMIT_DEFAULT] if Config.RATELIMIT_ENABLED else [],
    storage_uri=Config.RATELIMIT_STORAGE_URL
)

# Setup logging
logging.basicConfig(
    level=getattr(logging, Config.LOG_LEVEL),
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler(Config.LOG_FILE),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

# Ensure directories exist
os.makedirs(Config.FACES_DB_PATH, exist_ok=True)
os.makedirs(os.path.dirname(Config.LOG_FILE), exist_ok=True)


def decode_base64_image(base64_string):
    """
    Decode base64 image string to PIL Image
    """
    try:
        # Remove data:image prefix if present
        if 'base64,' in base64_string:
            base64_string = base64_string.split('base64,')[1]

        # Decode base64
        image_data = base64.b64decode(base64_string)

        # Check file size
        if len(image_data) > Config.MAX_FILE_SIZE:
            raise ValueError(f'Image size exceeds maximum allowed ({Config.MAX_FILE_SIZE} bytes)')

        # Open image
        image = Image.open(BytesIO(image_data))

        return image

    except Exception as e:
        logger.error(f'Error decoding base64 image: {str(e)}')
        raise


def save_temp_image(image, prefix='temp'):
    """
    Save PIL Image to temporary file
    """
    try:
        temp_dir = os.path.join(Config.FACES_DB_PATH, 'temp')
        os.makedirs(temp_dir, exist_ok=True)

        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S_%f')
        filename = f'{prefix}_{timestamp}.jpg'
        filepath = os.path.join(temp_dir, filename)

        # Convert to RGB if necessary
        if image.mode != 'RGB':
            image = image.convert('RGB')

        image.save(filepath, 'JPEG', quality=95)

        return filepath

    except Exception as e:
        logger.error(f'Error saving temp image: {str(e)}')
        raise


def cleanup_temp_file(filepath):
    """
    Remove temporary file
    """
    try:
        if os.path.exists(filepath):
            os.remove(filepath)
    except Exception as e:
        logger.warning(f'Error removing temp file {filepath}: {str(e)}')


def calculate_image_hash(filepath):
    """
    Calculate SHA-256 hash of image file
    """
    try:
        with open(filepath, 'rb') as f:
            return hashlib.sha256(f.read()).hexdigest()
    except Exception as e:
        logger.error(f'Error calculating image hash: {str(e)}')
        return None


@app.route('/health', methods=['GET'])
def health():
    """
    Health check endpoint
    """
    return jsonify({
        'status': 'ok',
        'service': 'deepface-api',
        'version': '1.0.0',
        'model': Config.MODEL_NAME,
        'detector': Config.DETECTOR_BACKEND,
        'timestamp': datetime.now().isoformat()
    }), 200


@app.route('/enroll', methods=['POST'])
@limiter.limit("20 per minute")
def enroll():
    """
    Enroll a new face
    Expected JSON:
    {
        "employee_id": "123",
        "photo": "base64_encoded_image"
    }
    """
    try:
        data = request.get_json()

        # Validate input
        if not data or 'employee_id' not in data or 'photo' not in data:
            return jsonify({
                'success': False,
                'error': 'Missing required fields: employee_id, photo'
            }), 400

        employee_id = str(data['employee_id'])
        photo_base64 = data['photo']

        logger.info(f'Enrolling face for employee {employee_id}')

        # Decode image
        image = decode_base64_image(photo_base64)

        # Save temporary image
        temp_path = save_temp_image(image, f'enroll_{employee_id}')

        try:
            # Extract faces using DeepFace
            faces = DeepFace.extract_faces(
                img_path=temp_path,
                detector_backend=Config.DETECTOR_BACKEND,
                enforce_detection=Config.ENFORCE_DETECTION,
                align=Config.ALIGN
            )

            # Validate face count
            if len(faces) == 0:
                cleanup_temp_file(temp_path)
                return jsonify({
                    'success': False,
                    'error': 'No face detected in the image'
                }), 400

            if len(faces) > 1:
                cleanup_temp_file(temp_path)
                return jsonify({
                    'success': False,
                    'error': f'Multiple faces detected ({len(faces)}). Please use a photo with only one face'
                }), 400

            face = faces[0]

            # Check face size
            face_region = face['facial_area']
            face_width = face_region['w']
            face_height = face_region['h']

            if face_width < Config.MIN_FACE_SIZE or face_height < Config.MIN_FACE_SIZE:
                cleanup_temp_file(temp_path)
                return jsonify({
                    'success': False,
                    'error': f'Face too small. Minimum size: {Config.MIN_FACE_SIZE}x{Config.MIN_FACE_SIZE} pixels'
                }), 400

            # Create employee directory
            employee_dir = os.path.join(Config.FACES_DB_PATH, employee_id)
            os.makedirs(employee_dir, exist_ok=True)

            # Save face image
            face_filename = f'{employee_id}_face.jpg'
            face_path = os.path.join(employee_dir, face_filename)

            # Copy temp file to employee directory
            image.save(face_path, 'JPEG', quality=95)

            # Calculate hash
            image_hash = calculate_image_hash(face_path)

            # Get face confidence
            confidence = face.get('confidence', 0)

            logger.info(f'Face enrolled successfully for employee {employee_id}')

            return jsonify({
                'success': True,
                'employee_id': employee_id,
                'face_path': face_path,
                'image_hash': image_hash,
                'confidence': float(confidence),
                'facial_area': face_region,
                'message': 'Face enrolled successfully'
            }), 200

        finally:
            cleanup_temp_file(temp_path)

    except ValueError as e:
        logger.error(f'Validation error in enroll: {str(e)}')
        return jsonify({
            'success': False,
            'error': str(e)
        }), 400

    except Exception as e:
        logger.error(f'Error in enroll: {str(e)}')
        return jsonify({
            'success': False,
            'error': 'Internal server error'
        }), 500


@app.route('/recognize', methods=['POST'])
@limiter.limit("10 per minute")
def recognize():
    """
    Recognize a face from the database
    Expected JSON:
    {
        "photo": "base64_encoded_image",
        "threshold": 0.40 (optional)
    }
    """
    try:
        data = request.get_json()

        # Validate input
        if not data or 'photo' not in data:
            return jsonify({
                'success': False,
                'error': 'Missing required field: photo'
            }), 400

        photo_base64 = data['photo']
        threshold = float(data.get('threshold', Config.get_threshold()))

        logger.info(f'Recognizing face with threshold {threshold}')

        # Decode image
        image = decode_base64_image(photo_base64)

        # Save temporary image
        temp_path = save_temp_image(image, 'recognize')

        try:
            # Find matching faces
            result = DeepFace.find(
                img_path=temp_path,
                db_path=Config.FACES_DB_PATH,
                model_name=Config.MODEL_NAME,
                detector_backend=Config.DETECTOR_BACKEND,
                distance_metric=Config.DISTANCE_METRIC,
                enforce_detection=Config.ENFORCE_DETECTION,
                align=Config.ALIGN,
                silent=True
            )

            # Check if any matches found
            if result is None or len(result) == 0 or len(result[0]) == 0:
                logger.info('No matching face found')
                return jsonify({
                    'success': True,
                    'recognized': False,
                    'message': 'No matching face found'
                }), 200

            # Get the best match (first result)
            matches = result[0]
            best_match = matches.iloc[0]

            distance = float(best_match['distance'])
            identity = best_match['identity']

            # Check if distance is below threshold
            if distance > threshold:
                logger.info(f'Match found but distance ({distance}) exceeds threshold ({threshold})')
                return jsonify({
                    'success': True,
                    'recognized': False,
                    'message': 'Face found but similarity too low',
                    'distance': distance,
                    'threshold': threshold
                }), 200

            # Extract employee_id from identity path
            # Format: ../storage/faces/employee_id/employee_id_face.jpg
            employee_id = os.path.basename(os.path.dirname(identity))

            # Calculate similarity percentage (inverse of distance)
            similarity = 1 - distance

            logger.info(f'Face recognized: employee {employee_id}, distance {distance}, similarity {similarity}')

            return jsonify({
                'success': True,
                'recognized': True,
                'employee_id': employee_id,
                'distance': distance,
                'similarity': float(similarity),
                'threshold': threshold,
                'model': Config.MODEL_NAME,
                'detector': Config.DETECTOR_BACKEND,
                'message': 'Face recognized successfully'
            }), 200

        finally:
            cleanup_temp_file(temp_path)

    except ValueError as e:
        logger.error(f'Validation error in recognize: {str(e)}')
        return jsonify({
            'success': False,
            'error': str(e)
        }), 400

    except Exception as e:
        logger.error(f'Error in recognize: {str(e)}')
        return jsonify({
            'success': False,
            'error': 'Internal server error'
        }), 500


@app.route('/verify', methods=['POST'])
@limiter.limit("20 per minute")
def verify():
    """
    Verify if two faces are the same person
    Expected JSON:
    {
        "photo1": "base64_encoded_image",
        "photo2": "base64_encoded_image"
    }
    """
    try:
        data = request.get_json()

        # Validate input
        if not data or 'photo1' not in data or 'photo2' not in data:
            return jsonify({
                'success': False,
                'error': 'Missing required fields: photo1, photo2'
            }), 400

        photo1_base64 = data['photo1']
        photo2_base64 = data['photo2']

        logger.info('Verifying two faces')

        # Decode images
        image1 = decode_base64_image(photo1_base64)
        image2 = decode_base64_image(photo2_base64)

        # Save temporary images
        temp_path1 = save_temp_image(image1, 'verify1')
        temp_path2 = save_temp_image(image2, 'verify2')

        try:
            # Verify faces
            result = DeepFace.verify(
                img1_path=temp_path1,
                img2_path=temp_path2,
                model_name=Config.MODEL_NAME,
                detector_backend=Config.DETECTOR_BACKEND,
                distance_metric=Config.DISTANCE_METRIC,
                enforce_detection=Config.ENFORCE_DETECTION,
                align=Config.ALIGN
            )

            verified = result['verified']
            distance = result['distance']
            threshold = result['threshold']

            similarity = 1 - distance

            logger.info(f'Verification result: {verified}, distance: {distance}')

            return jsonify({
                'success': True,
                'verified': bool(verified),
                'distance': float(distance),
                'similarity': float(similarity),
                'threshold': float(threshold),
                'model': Config.MODEL_NAME,
                'message': 'Faces verified successfully'
            }), 200

        finally:
            cleanup_temp_file(temp_path1)
            cleanup_temp_file(temp_path2)

    except ValueError as e:
        logger.error(f'Validation error in verify: {str(e)}')
        return jsonify({
            'success': False,
            'error': str(e)
        }), 400

    except Exception as e:
        logger.error(f'Error in verify: {str(e)}')
        return jsonify({
            'success': False,
            'error': 'Internal server error'
        }), 500


@app.route('/analyze', methods=['POST'])
@limiter.limit("20 per minute")
def analyze():
    """
    Analyze face attributes (age, gender, emotion, race)
    Expected JSON:
    {
        "photo": "base64_encoded_image"
    }
    """
    try:
        data = request.get_json()

        # Validate input
        if not data or 'photo' not in data:
            return jsonify({
                'success': False,
                'error': 'Missing required field: photo'
            }), 400

        photo_base64 = data['photo']

        logger.info('Analyzing face attributes')

        # Decode image
        image = decode_base64_image(photo_base64)

        # Save temporary image
        temp_path = save_temp_image(image, 'analyze')

        try:
            # Analyze face
            analysis = DeepFace.analyze(
                img_path=temp_path,
                actions=['age', 'gender', 'emotion', 'race'],
                detector_backend=Config.DETECTOR_BACKEND,
                enforce_detection=Config.ENFORCE_DETECTION,
                silent=True
            )

            # Extract results (first face)
            if isinstance(analysis, list):
                analysis = analysis[0]

            result = {
                'age': int(analysis['age']),
                'gender': analysis['dominant_gender'],
                'emotion': analysis['dominant_emotion'],
                'race': analysis['dominant_race'],
                'facial_area': analysis['region']
            }

            logger.info(f'Face analyzed: {result}')

            return jsonify({
                'success': True,
                **result,
                'message': 'Face analyzed successfully'
            }), 200

        finally:
            cleanup_temp_file(temp_path)

    except ValueError as e:
        logger.error(f'Validation error in analyze: {str(e)}')
        return jsonify({
            'success': False,
            'error': str(e)
        }), 400

    except Exception as e:
        logger.error(f'Error in analyze: {str(e)}')
        return jsonify({
            'success': False,
            'error': 'Internal server error'
        }), 500


@app.errorhandler(429)
def ratelimit_error(e):
    """Rate limit exceeded handler"""
    return jsonify({
        'success': False,
        'error': 'Rate limit exceeded. Please try again later.'
    }), 429


@app.errorhandler(404)
def not_found(e):
    """404 handler"""
    return jsonify({
        'success': False,
        'error': 'Endpoint not found'
    }), 404


@app.errorhandler(500)
def internal_error(e):
    """500 handler"""
    logger.error(f'Internal server error: {str(e)}')
    return jsonify({
        'success': False,
        'error': 'Internal server error'
    }), 500


if __name__ == '__main__':
    logger.info(f'Starting DeepFace API on {Config.HOST}:{Config.PORT}')
    logger.info(f'Model: {Config.MODEL_NAME}, Detector: {Config.DETECTOR_BACKEND}')
    logger.info(f'Faces DB: {Config.FACES_DB_PATH}')

    app.run(
        host=Config.HOST,
        port=Config.PORT,
        debug=Config.DEBUG
    )
