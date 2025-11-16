"""
DeepFace API Configuration
"""

import os
from dotenv import load_dotenv

# Load environment variables
load_dotenv()


class Config:
    """Base configuration"""

    # Flask
    SECRET_KEY = os.getenv('SECRET_KEY', 'dev-secret-key-change-in-production')
    DEBUG = os.getenv('DEBUG', 'False').lower() == 'true'

    # Server
    HOST = os.getenv('HOST', '0.0.0.0')
    PORT = int(os.getenv('PORT', 5000))

    # DeepFace Settings
    MODEL_NAME = os.getenv('MODEL_NAME', 'VGG-Face')  # VGG-Face, Facenet, Facenet512, OpenFace, DeepFace, DeepID, ArcFace, Dlib
    DETECTOR_BACKEND = os.getenv('DETECTOR_BACKEND', 'opencv')  # opencv, ssd, dlib, mtcnn, retinaface, mediapipe
    DISTANCE_METRIC = os.getenv('DISTANCE_METRIC', 'cosine')  # cosine, euclidean, euclidean_l2
    ENFORCE_DETECTION = os.getenv('ENFORCE_DETECTION', 'True').lower() == 'true'
    ALIGN = os.getenv('ALIGN', 'True').lower() == 'true'

    # Recognition Thresholds (distance - lower is better)
    THRESHOLD = float(os.getenv('THRESHOLD', 0.40))  # For VGG-Face cosine

    # Thresholds by model and metric (DeepFace defaults)
    THRESHOLDS = {
        'VGG-Face': {'cosine': 0.40, 'euclidean': 0.60, 'euclidean_l2': 0.86},
        'Facenet': {'cosine': 0.40, 'euclidean': 10, 'euclidean_l2': 0.80},
        'Facenet512': {'cosine': 0.30, 'euclidean': 23.56, 'euclidean_l2': 1.04},
        'ArcFace': {'cosine': 0.68, 'euclidean': 4.15, 'euclidean_l2': 1.13},
        'Dlib': {'cosine': 0.07, 'euclidean': 0.6, 'euclidean_l2': 0.4},
        'SFace': {'cosine': 0.593, 'euclidean': 10.734, 'euclidean_l2': 1.055},
        'OpenFace': {'cosine': 0.10, 'euclidean': 0.55, 'euclidean_l2': 0.55},
        'DeepFace': {'cosine': 0.23, 'euclidean': 64, 'euclidean_l2': 0.64},
        'DeepID': {'cosine': 0.015, 'euclidean': 45, 'euclidean_l2': 0.17}
    }

    # Face Database
    FACES_DB_PATH = os.getenv('FACES_DB_PATH', '../storage/faces')

    # Upload Settings
    MAX_FILE_SIZE = int(os.getenv('MAX_FILE_SIZE', 5 * 1024 * 1024))  # 5MB
    ALLOWED_EXTENSIONS = {'jpg', 'jpeg', 'png'}

    # Anti-Spoofing
    ANTI_SPOOFING_ENABLED = os.getenv('ANTI_SPOOFING_ENABLED', 'True').lower() == 'true'
    MIN_FACE_SIZE = int(os.getenv('MIN_FACE_SIZE', 80))  # Minimum face size in pixels

    # CORS
    CORS_ORIGINS = os.getenv('CORS_ORIGINS', 'http://localhost:8000,http://localhost:8080').split(',')

    # Rate Limiting
    RATELIMIT_ENABLED = os.getenv('RATELIMIT_ENABLED', 'True').lower() == 'true'
    RATELIMIT_DEFAULT = os.getenv('RATELIMIT_DEFAULT', '100 per minute')
    RATELIMIT_STORAGE_URL = os.getenv('RATELIMIT_STORAGE_URL', 'memory://')

    # Logging
    LOG_LEVEL = os.getenv('LOG_LEVEL', 'INFO')
    LOG_FILE = os.getenv('LOG_FILE', 'logs/deepface_api.log')

    # Cache
    CACHE_ENABLED = os.getenv('CACHE_ENABLED', 'True').lower() == 'true'
    CACHE_TTL = int(os.getenv('CACHE_TTL', 300))  # 5 minutes

    @staticmethod
    def get_threshold():
        """Get threshold based on model and metric"""
        model = Config.MODEL_NAME
        metric = Config.DISTANCE_METRIC

        if model in Config.THRESHOLDS and metric in Config.THRESHOLDS[model]:
            return Config.THRESHOLDS[model][metric]

        return Config.THRESHOLD

    @staticmethod
    def allowed_file(filename):
        """Check if file extension is allowed"""
        return '.' in filename and \
               filename.rsplit('.', 1)[1].lower() in Config.ALLOWED_EXTENSIONS


class DevelopmentConfig(Config):
    """Development configuration"""
    DEBUG = True
    LOG_LEVEL = 'DEBUG'


class ProductionConfig(Config):
    """Production configuration"""
    DEBUG = False
    LOG_LEVEL = 'WARNING'


# Configuration dictionary
config = {
    'development': DevelopmentConfig,
    'production': ProductionConfig,
    'default': DevelopmentConfig
}
