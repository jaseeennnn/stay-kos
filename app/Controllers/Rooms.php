<?php

namespace App\Controllers;

use App\Models\RoomModel;

class Rooms extends BaseController
{
    protected RoomModel $roomModel;

    /** Folder simpan foto kamar, relatif dari public/ */
    private const UPLOAD_PATH = FCPATH . 'uploads/rooms/';
    private const UPLOAD_URL  = 'uploads/rooms/';

    public function __construct()
    {
        $this->roomModel = new RoomModel();

        // Pastikan folder upload ada
        if (! is_dir(self::UPLOAD_PATH)) {
            mkdir(self::UPLOAD_PATH, 0755, true);
        }
    }

    private function uploadImage(): ?string
    {
        $file = $this->request->getFile('image');
        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return null;
        }
 
        $newName = $file->getRandomName();
        $file->move(self::UPLOAD_PATH, $newName);
 
        return $newName;
    }


    public function index()
    {
        return view('admin/rooms/index', ['title' => 'Manajemen Kamar']);
    }

    public function getData()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Forbidden']);
        }

        $params = $this->request->getPost();
        $result = $this->roomModel->getDataTable($params);

        foreach ($result['data'] as &$row) {
            $statusBadge = match ($row['status']) {
                'available'   => '<span class="badge bg-success">Tersedia</span>',
                'booked'      => '<span class="badge bg-warning text-dark">Disewa</span>',
                'maintenance' => '<span class="badge bg-secondary">Maintenance</span>',
                default       => '<span class="badge bg-light text-dark">' . esc($row['status']) . '</span>',
            };

            // Thumbnail foto
            $imgSrc = $row['image']
                ? base_url(self::UPLOAD_URL . esc($row['image']))
                : null;
 
            $thumb = $imgSrc
                ? '<img src="' . $imgSrc . '" alt="foto"
                        class="rounded-2 room-thumb-admin"
                        style="width:54px;height:42px;object-fit:cover;cursor:pointer;"
                        data-src="' . $imgSrc . '">'
                : '<div class="rounded-2 d-flex align-items-center justify-content-center bg-light text-muted"
                        style="width:54px;height:42px;font-size:1.2rem;">
                       <i class="bi bi-image"></i>
                   </div>';

            $actions = '
                <button class="btn btn-sm btn-warning btn-edit me-1" data-id="' . $row['id'] . '">
                    <i class="bi bi-pencil"></i> Edit
                </button>
                <button class="btn btn-sm btn-danger btn-delete" data-id="' . $row['id'] . '">
                    <i class="bi bi-trash"></i> Hapus
                </button>';

            $row['image']      = $thumb;
            $row['price']  = 'Rp ' . number_format($row['price'], 0, ',', '.');
            $row['status'] = $statusBadge;
            $row['action'] = $actions;
        }

        return $this->response->setJSON([
            'draw'            => (int)($params['draw'] ?? 1),
            'recordsTotal'    => $result['recordsTotal'],
            'recordsFiltered' => $result['recordsFiltered'],
            'data'            => $result['data'],
            'csrf'            => csrf_hash(),
        ]);
    }

    public function store()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Forbidden']);
        }

        $rules = [
            'room_name' => 'required|max_length[100]',
            'price'     => 'required|numeric',
            'status'    => 'required|in_list[available,booked,maintenance]',
            'image'     => 'if_exist|max_size[image,3072]|is_image[image]|mime_in[image,image/jpeg,image/png,image/webp]',
        ];

        if (! $this->validate($rules)) {
            return $this->jsonResponse('error', 'Validasi gagal.', ['errors' => $this->validator->getErrors()]);
        }

        $filename = $this->uploadImage();

        $this->roomModel->insert([
            'room_name'  => $this->request->getPost('room_name'),
            'price'      => $this->request->getPost('price'),
            'facilities' => $this->request->getPost('facilities'),
            'status'     => $this->request->getPost('status'),
            'image'      => $filename,
        ]);

        return $this->jsonResponse('success', 'Kamar berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Forbidden']);
        }

        $room = $this->roomModel->find($id);
        if (! $room) {
            return $this->jsonResponse('error', 'Kamar tidak ditemukan.', [], 404);
        }

        // Sertakan URL lengkap foto agar view bisa preview langsung
        $room['image_url'] = $room['image']
            ? base_url(self::UPLOAD_URL . $room['image'])
            : null;


        return $this->response->setJSON(['status' => 'success', 'data' => $room, 'csrf' => csrf_hash()]);
    }

    public function update(int $id)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Forbidden']);
        }

        $rules = [
            'room_name' => 'required|max_length[100]',
            'price'     => 'required|numeric',
            'status'    => 'required|in_list[available,booked,maintenance]',
            'image'     => 'if_exist|max_size[image,3072]|is_image[image]|mime_in[image,image/jpeg,image/png,image/webp]',
        ];

        if (! $this->validate($rules)) {
            return $this->jsonResponse('error', 'Validasi gagal.', ['errors' => $this->validator->getErrors()]);
        }

        $room = $this->roomModel->find($id);
        if (! $room) {
            return $this->jsonResponse('error', 'Kamar tidak ditemukan.', [], 404);
        }
 
        $data = [
            'room_name'  => $this->request->getPost('room_name'),
            'price'      => $this->request->getPost('price'),
            'facilities' => $this->request->getPost('facilities'),
            'status'     => $this->request->getPost('status'),
        ];
 
        // Upload foto baru jika ada
        $newFile = $this->uploadImage();
        if ($newFile !== null) {
            // Hapus foto lama
            if ($room['image'] && file_exists(self::UPLOAD_PATH . $room['image'])) {
                @unlink(self::UPLOAD_PATH . $room['image']);
            }
            $data['image'] = $newFile;
        }
 
        // Hapus foto jika admin centang "hapus foto"
        if ($this->request->getPost('remove_image') === '1' && $newFile === null) {
            if ($room['image'] && file_exists(self::UPLOAD_PATH . $room['image'])) {
                @unlink(self::UPLOAD_PATH . $room['image']);
            }
            $data['image'] = null;
        }

        $this->roomModel->update($id, $data);

        return $this->jsonResponse('success', 'Kamar berhasil diperbarui.');
    }

    public function delete(int $id)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Forbidden']);
        }

        $room = $this->roomModel->find($id);
        if ($room && $room['image'] && file_exists(self::UPLOAD_PATH . $room['image'])) {
            @unlink(self::UPLOAD_PATH . $room['image']);
        }

        $this->roomModel->delete($id);

        return $this->jsonResponse('success', 'Kamar berhasil dihapus.');
    }

    public function userIndex()
    {
        $rooms = $this->roomModel->getAvailableRooms();
        return view('user/rooms', [
            'rooms' => $rooms,
            'title' => 'Kamar Tersedia',
            'uploadUrl' => base_url(self::UPLOAD_URL),
        ]);
    }
}