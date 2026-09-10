<?php declare(strict_types = 1);

namespace TheSaiged\Tests\Unit\Admins;

use TheSaiged\Admins\AdminCreate;
use TheSaiged\Admins\AdminRecord;
use TheSaiged\Admins\AdminRepository;
use TheSaiged\Admins\AdminRole;
use TheSaiged\Admins\AdminService;
use TheSaiged\Admins\DuplicateEmailException;
use TheSaiged\Admins\LastAdminException;
use TheSaiged\Core\Container;
use TheSaiged\Tests\TestCase;

final class AdminServiceTest extends TestCase {

    function testIsAuthorizedReturnsExistingRecord (): void {
        $repo = $this->createMock(AdminRepository::class);
        $repo->method('findByEmail')->with('a@x.com')
            ->willReturn(new AdminRecord('a@x.com', AdminRole::Editor));
        Container::set(AdminRepository::class, $repo);

        $record = Container::get(AdminService::class)->isAuthorized('a@x.com');

        $this->assertNotNull($record);
        $this->assertSame(AdminRole::Editor, $record->role);
    }

    function testIsAuthorizedReturnsNullForUnknownEmailWhenTableNotEmpty (): void {
        $repo = $this->createMock(AdminRepository::class);
        $repo->method('findByEmail')->willReturn(null);
        $repo->method('count')->willReturn(1);
        Container::set(AdminRepository::class, $repo);

        $this->assertNull(Container::get(AdminService::class)->isAuthorized('nobody@x.com'));
    }

    function testIsAuthorizedBootstrapsInitialAdminWhenTableEmpty (): void {
        putenv('INITIAL_ADMIN_EMAIL=boss@x.com');

        $repo = $this->createMock(AdminRepository::class);
        $repo->method('findByEmail')
            ->willReturnOnConsecutiveCalls(null, new AdminRecord('boss@x.com', AdminRole::Admin));
        $repo->method('count')->willReturn(0);
        $repo->expects($this->once())->method('insert')
            ->with($this->callback(fn (AdminCreate $c) => $c->email === 'boss@x.com' && $c->role === AdminRole::Admin));
        Container::set(AdminRepository::class, $repo);

        $record = Container::get(AdminService::class)->isAuthorized('boss@x.com');

        $this->assertNotNull($record);
        $this->assertSame(AdminRole::Admin, $record->role);

        putenv('INITIAL_ADMIN_EMAIL');
    }

    function testIsAuthorizedDoesNotBootstrapAnUnmatchedEmail (): void {
        putenv('INITIAL_ADMIN_EMAIL=boss@x.com');

        $repo = $this->createMock(AdminRepository::class);
        $repo->method('findByEmail')->willReturn(null);
        $repo->method('count')->willReturn(0);
        $repo->expects($this->never())->method('insert');
        Container::set(AdminRepository::class, $repo);

        $this->assertNull(Container::get(AdminService::class)->isAuthorized('someone-else@x.com'));

        putenv('INITIAL_ADMIN_EMAIL');
    }

    function testAddAdminThrowsOnDuplicateEmail (): void {
        $repo = $this->createMock(AdminRepository::class);
        $repo->method('findByEmail')->willReturn(new AdminRecord('a@x.com', AdminRole::Editor));
        Container::set(AdminRepository::class, $repo);

        $this->expectException(DuplicateEmailException::class);
        Container::get(AdminService::class)->addAdmin(new AdminCreate('a@x.com', AdminRole::Editor));
    }

    function testUpdateRoleReturnsFalseForUnknownEmail (): void {
        $repo = $this->createMock(AdminRepository::class);
        $repo->method('findByEmail')->willReturn(null);
        Container::set(AdminRepository::class, $repo);

        $this->assertFalse(Container::get(AdminService::class)->updateRole('nobody@x.com', AdminRole::Editor));
    }

    function testUpdateRoleThrowsWhenDemotingTheLastAdmin (): void {
        $repo = $this->createMock(AdminRepository::class);
        $repo->method('findByEmail')->willReturn(new AdminRecord('a@x.com', AdminRole::Admin));
        $repo->method('countByRole')->with(AdminRole::Admin)->willReturn(1);
        Container::set(AdminRepository::class, $repo);

        $this->expectException(LastAdminException::class);
        Container::get(AdminService::class)->updateRole('a@x.com', AdminRole::Editor);
    }

    function testUpdateRoleAllowsDemotingWhenAnotherAdminRemains (): void {
        $repo = $this->createMock(AdminRepository::class);
        $repo->method('findByEmail')->willReturn(new AdminRecord('a@x.com', AdminRole::Admin));
        $repo->method('countByRole')->with(AdminRole::Admin)->willReturn(2);
        $repo->expects($this->once())->method('updateRole')->with('a@x.com', AdminRole::Editor)->willReturn(true);
        Container::set(AdminRepository::class, $repo);

        $this->assertTrue(Container::get(AdminService::class)->updateRole('a@x.com', AdminRole::Editor));
    }

    function testRemoveAdminThrowsWhenRemovingTheLastAdmin (): void {
        $repo = $this->createMock(AdminRepository::class);
        $repo->method('findByEmail')->willReturn(new AdminRecord('a@x.com', AdminRole::Admin));
        $repo->method('countByRole')->with(AdminRole::Admin)->willReturn(1);
        Container::set(AdminRepository::class, $repo);

        $this->expectException(LastAdminException::class);
        Container::get(AdminService::class)->removeAdmin('a@x.com');
    }

    function testRemoveAdminAllowsRemovingAnEditor (): void {
        $repo = $this->createMock(AdminRepository::class);
        $repo->method('findByEmail')->willReturn(new AdminRecord('a@x.com', AdminRole::Editor));
        $repo->expects($this->once())->method('delete')->with('a@x.com')->willReturn(true);
        Container::set(AdminRepository::class, $repo);

        $this->assertTrue(Container::get(AdminService::class)->removeAdmin('a@x.com'));
    }

}
