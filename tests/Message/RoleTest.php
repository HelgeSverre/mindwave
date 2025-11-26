<?php

use Mindwave\Mindwave\Message\Role;

describe('Role Enum', function () {
    it('has system role with value "system"', function () {
        expect(Role::system->value)->toBe('system');
    });

    it('has user role with value "user"', function () {
        expect(Role::user->value)->toBe('user');
    });

    it('has ai role with value "ai"', function () {
        expect(Role::ai->value)->toBe('ai');
    });

    it('has function role with value "function"', function () {
        expect(Role::function->value)->toBe('function');
    });

    it('returns enum case from valid string using tryFrom', function () {
        expect(Role::tryFrom('system'))->toBe(Role::system);
        expect(Role::tryFrom('user'))->toBe(Role::user);
        expect(Role::tryFrom('ai'))->toBe(Role::ai);
        expect(Role::tryFrom('function'))->toBe(Role::function);
    });

    it('returns null for invalid string using tryFrom', function () {
        expect(Role::tryFrom('invalid'))->toBeNull();
        expect(Role::tryFrom(''))->toBeNull();
        expect(Role::tryFrom('assistant'))->toBeNull();
    });

    it('has exactly four cases', function () {
        expect(Role::cases())->toHaveCount(4);
    });
});
