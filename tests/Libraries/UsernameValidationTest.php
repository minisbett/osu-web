<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

declare(strict_types=1);

namespace Tests\Libraries;

use App\Libraries\UsernameValidation;
use App\Models\RankHighest;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

// FIXME: need more tests
class UsernameValidationTest extends TestCase
{
    public function testUsersOfUsernameIncludesCurrentUsernameOwner(): void
    {
        $existing = User::factory()->create([
            'username' => 'user1',
            'username_clean' => 'user1',
            'user_lastvisit' => Carbon::now()->subYears(),
        ]);

        $users = UsernameValidation::usersOfUsername('user1');
        $this->assertCount(1, $users);
        $this->assertTrue($existing->is($users->first()));
    }

    /**
     * @dataProvider usernameValidationDataProvider
     */
    public function testValidateUsername(string $username, bool $expectValid): void
    {
        $this->assertSame(
            $expectValid,
            UsernameValidation::validateUsername($username)->isEmpty(),
        );
    }

    public function testValidateUsersOfUsernameInactive(): void
    {
        $existing = User::factory()->create([
            'username' => 'user1',
            'username_clean' => 'user1',
            'user_lastvisit' => Carbon::now()->subYears(20),
        ]);

        $this->assertFalse(UsernameValidation::validateUsersOfUsername('user1')->isAny());
    }

    public function testValidateUsersOfUsernameInactiveFormerTopRank(): void
    {
        $existing = User::factory()->create([
            'username' => 'user1',
            'username_clean' => 'user1',
            'user_lastvisit' => Carbon::now()->subYears(20),
        ]);
        RankHighest::factory()->create([
            'user_id' => $existing,
            'rank' => 100,
        ]);

        $this->assertTrue(UsernameValidation::validateUsersOfUsername('user1')->isAny());
    }

    public function testValidateUsersOfUsernameRenamedTopRank(): void
    {
        $existing = User::factory()->create([
            'username' => 'user2',
            'username_clean' => 'user2',
            'user_lastvisit' => Carbon::now(),
        ]);
        $existing->usernameChangeHistory()->make([
            'timestamp' => Carbon::now()->subYears(20),
            'username' => 'user2',
            'username_last' => 'user1',
        ])->saveOrExplode();
        RankHighest::factory()->create([
            'user_id' => $existing,
            'rank' => 100,
        ]);

        $this->assertTrue(UsernameValidation::validateUsersOfUsername('user1')->isAny());
    }

    public function usernameValidationDataProvider(): array
    {
        return [
            'alphabetic'                   => ['Username',         true],
            'alphanumeric'                 => ['Username1000',     true],
            'numeric'                      => ['1000',             true],
            'space at beginning'           => [' Username',        false],
            'space at end'                 => ['Username ',        false],
            'space in middle'              => ['Username 1000',    true],
            'too short'                    => ['aa',               false],
            'shortest'                     => ['aaa',              true],
            'too long'                     => ['aaaaaaaaaaaaaaaa', false],
            'longest'                      => ['aaaaaaaaaaaaaaa',  true],
            'two spaces in middle'         => ['Username  1000',   false],
            'invalid special characters'   => ['Usern@me',         false],
            'all valid special characters' => ['-[]_',             true],
            'mixed space and underscore'   => ['Username_1 2',     false],
        ];
    }
}
