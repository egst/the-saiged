<?php declare(strict_types = 1);

namespace TheSaiged\Controllers;

use Throwable;
use TheSaiged\Admins\AdminCreate;
use TheSaiged\Admins\AdminRecord;
use TheSaiged\Admins\AdminRole;
use TheSaiged\Admins\AdminService;
use TheSaiged\Admins\DuplicateEmailException;
use TheSaiged\Admins\LastAdminException;
use TheSaiged\Core\Controller;
use TheSaiged\Core\Http\Exception\BadRequestException;
use TheSaiged\Core\Http\Exception\ConflictException;
use TheSaiged\Core\Http\Exception\HttpException;
use TheSaiged\Core\Http\Exception\NotFoundException;
use TheSaiged\Core\Http\Request;
use TheSaiged\Core\Http\Response;
use TheSaiged\Core\InvalidDataException;

/**
 * Admin JSON API for admin accounts themselves (/admin/site/users).
 * Named plural — Controllers\AdminController already exists for the
 * general Pages/Sections admin API and "Admin" there means "the admin
 * panel", not "an account with the Admin role".
 *
 * Every route here is wrapped in AdminGuard::admin (see Entry::routes)
 * on top of this controller's own logic — the guard re-checks the
 * caller's role fresh from the DB on every call.
 */
final class AdminsController {

    use Controller;

    function __construct (
        private AdminService $admins,
    ) {}

    function list (Request $request): Response {
        $records = array_map(
            fn (AdminRecord $record): array => $record->toArray(),
            $this->admins->list(),
        );
        return Response::json(['admins' => $records]);
    }

    /**
     * @throws BadRequestException missing body / invalid shape
     */
    function create (Request $request): Response {
        $body   = $request->bodyObject() ?? throw new BadRequestException('Expected JSON body');
        $create = AdminCreate::fromArray($body);
        $record = $this->admins->addAdmin($create);
        return Response::json(['ok' => true, 'admin' => $record->toArray()], 201);
    }

    /**
     * @throws BadRequestException missing path/body param
     * @throws NotFoundException   unknown email
     */
    function updateRole (Request $request): Response {
        $email   = $request->path->getString('email') ?? throw new BadRequestException('Missing email');
        $body    = $request->bodyObject() ?? throw new BadRequestException('Expected JSON body');
        $roleRaw = $body['role'] ?? null;
        if (!is_string($roleRaw))
            throw new BadRequestException('Expected role (string)');
        $role = AdminRole::tryFrom($roleRaw) ?? throw new BadRequestException('Unknown role');

        if (!$this->admins->updateRole($email, $role))
            throw new NotFoundException();
        return Response::json(['ok' => true]);
    }

    /**
     * @throws BadRequestException missing path param
     * @throws NotFoundException   unknown email
     */
    function remove (Request $request): Response {
        $email = $request->path->getString('email') ?? throw new BadRequestException('Missing email');
        if (!$this->admins->removeAdmin($email))
            throw new NotFoundException();
        return Response::json(['ok' => true]);
    }

    function onError (Throwable $exception, Request $request): Response {
        if ($exception instanceof InvalidDataException)
            $exception = new BadRequestException($exception->getMessage(), $exception);
        if ($exception instanceof DuplicateEmailException)
            $exception = new ConflictException($exception->getMessage(), $exception);
        if ($exception instanceof LastAdminException)
            $exception = new ConflictException($exception->getMessage(), $exception);
        if ($exception instanceof HttpException)
            return Response::json(['error' => $exception->getMessage()], $exception->status);
        error_log((string) $exception);
        return Response::json(['error' => 'Internal error'], 500);
    }

}
