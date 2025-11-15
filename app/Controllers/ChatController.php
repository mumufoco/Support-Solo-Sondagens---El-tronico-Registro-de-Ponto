<?php

namespace App\Controllers;

use App\Services\ChatService;
use App\Models\EmployeeModel;

/**
 * Chat Controller
 *
 * Handles web interface for chat functionality
 */
class ChatController extends BaseController
{
    protected ChatService $chatService;
    protected EmployeeModel $employeeModel;

    public function __construct()
    {
        $this->chatService = new ChatService();
        $this->employeeModel = new EmployeeModel();
    }

    /**
     * Get authenticated employee
     */
    protected function getAuthenticatedEmployee(): ?array
    {
        $employeeId = session()->get('employee_id');

        if (!$employeeId) {
            return null;
        }

        $employee = $this->employeeModel->find($employeeId);

        return $employee ? (array) $employee : null;
    }

    /**
     * Chat interface (main page)
     *
     * GET /chat
     */
    public function index()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return redirect()->to('/login')->with('error', 'Você precisa estar autenticado.');
        }

        // Get employee's rooms
        $rooms = $this->chatService->getEmployeeRooms($employee['id']);

        // Get online users
        $onlineUsers = $this->chatService->getOnlineUsers();

        // Get all employees for new chat
        $employees = $this->employeeModel->where('active', true)
            ->where('id !=', $employee['id'])
            ->orderBy('name', 'ASC')
            ->findAll();

        $data = [
            'title'       => 'Chat',
            'employee'    => $employee,
            'rooms'       => $rooms,
            'onlineUsers' => $onlineUsers,
            'employees'   => $employees,
        ];

        return view('chat/index', $data);
    }

    /**
     * View specific room
     *
     * GET /chat/room/{roomId}
     */
    public function room($roomId)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return redirect()->to('/login')->with('error', 'Você precisa estar autenticado.');
        }

        // Get room messages
        $result = $this->chatService->getRoomMessages($roomId, $employee['id'], 50, 0);

        if (!$result['success']) {
            return redirect()->to('/chat')->with('error', $result['message']);
        }

        // Get room members
        $membersResult = $this->chatService->getRoomMembers($roomId, $employee['id']);

        $data = [
            'title'    => 'Chat - Sala',
            'employee' => $employee,
            'roomId'   => $roomId,
            'messages' => $result['messages'],
            'members'  => $membersResult['members'] ?? [],
        ];

        return view('chat/room', $data);
    }

    /**
     * Start new private chat
     *
     * GET /chat/new/{employeeId}
     */
    public function newChat($employeeId)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return redirect()->to('/login')->with('error', 'Você precisa estar autenticado.');
        }

        // Get or create private room
        $result = $this->chatService->getOrCreatePrivateRoom($employee['id'], $employeeId);

        if (!$result['success']) {
            return redirect()->to('/chat')->with('error', $result['message']);
        }

        return redirect()->to('/chat/room/' . $result['room']->id);
    }

    /**
     * Create group chat
     *
     * GET /chat/group/create
     */
    public function createGroup()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return redirect()->to('/login')->with('error', 'Você precisa estar autenticado.');
        }

        // Get all employees
        $employees = $this->employeeModel->where('active', true)
            ->where('id !=', $employee['id'])
            ->orderBy('name', 'ASC')
            ->findAll();

        $data = [
            'title'     => 'Criar Grupo',
            'employee'  => $employee,
            'employees' => $employees,
        ];

        return view('chat/create_group', $data);
    }

    /**
     * Store new group
     *
     * POST /chat/group/store
     */
    public function storeGroup()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return redirect()->to('/login')->with('error', 'Você precisa estar autenticado.');
        }

        $rules = [
            'name'    => 'required|min_length[3]|max_length[255]',
            'members' => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $name = $this->request->getPost('name');
        $members = $this->request->getPost('members');

        if (!is_array($members)) {
            $members = explode(',', $members);
        }

        // Create group
        $result = $this->chatService->createGroupRoom($employee['id'], $name, $members);

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->to('/chat/room/' . $result['room']->id)
            ->with('success', 'Grupo criado com sucesso!');
    }

    /**
     * Room settings
     *
     * GET /chat/room/{roomId}/settings
     */
    public function roomSettings($roomId)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return redirect()->to('/login')->with('error', 'Você precisa estar autenticado.');
        }

        // Get room members
        $membersResult = $this->chatService->getRoomMembers($roomId, $employee['id']);

        if (!$membersResult['success']) {
            return redirect()->to('/chat')->with('error', $membersResult['message']);
        }

        // Get all employees for adding
        $employees = $this->employeeModel->where('active', true)
            ->orderBy('name', 'ASC')
            ->findAll();

        $data = [
            'title'     => 'Configurações da Sala',
            'employee'  => $employee,
            'roomId'    => $roomId,
            'members'   => $membersResult['members'],
            'employees' => $employees,
        ];

        return view('chat/settings', $data);
    }

    /**
     * Add member to room
     *
     * POST /chat/room/{roomId}/add-member
     */
    public function addMember($roomId)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Não autenticado.',
            ]);
        }

        $newMemberId = $this->request->getPost('employee_id');

        if (!$newMemberId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'ID do funcionário é obrigatório.',
            ]);
        }

        $result = $this->chatService->addMember($roomId, $employee['id'], $newMemberId);

        return $this->response->setJSON($result);
    }

    /**
     * Remove member from room
     *
     * POST /chat/room/{roomId}/remove-member
     */
    public function removeMember($roomId)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Não autenticado.',
            ]);
        }

        $memberToRemove = $this->request->getPost('employee_id');

        if (!$memberToRemove) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'ID do funcionário é obrigatório.',
            ]);
        }

        $result = $this->chatService->removeMember($roomId, $employee['id'], $memberToRemove);

        return $this->response->setJSON($result);
    }

    /**
     * Search messages
     *
     * GET /chat/room/{roomId}/search
     */
    public function search($roomId)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Não autenticado.',
            ]);
        }

        $query = $this->request->getGet('q');

        if (!$query) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Query de busca é obrigatória.',
            ]);
        }

        $result = $this->chatService->searchMessages($roomId, $employee['id'], $query);

        return $this->response->setJSON($result);
    }
}
