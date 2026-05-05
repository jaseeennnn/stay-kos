<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Services\DashboardService;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;
    protected $request;
    protected $helpers = ['url', 'form', 'array', 'upload'];
    protected $dashboardService;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');

        // ── Inject global view data untuk semua halaman ──────────────────────
        $this->dashboardService = new DashboardService();

        $data = $this->dashboardService->getPendingData();

        \Config\Services::renderer()->setData($data);
    }

    /**
     * Standarisasi JSON response untuk AJAX.
     * Selalu menyertakan CSRF token terbaru.
     */
    protected function jsonResponse(
        string $status,
        string $message,
        array $extra = [],
        int $code = 200
    ): ResponseInterface {
        return $this->response
            ->setStatusCode($code)
            ->setJSON(array_merge([
                'status'  => $status,
                'message' => $message,
                'csrf'    => csrf_hash(),
            ], $extra));
    }
}
