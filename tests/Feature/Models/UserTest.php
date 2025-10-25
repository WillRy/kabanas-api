<?php

namespace Tests\Feature\Models;

use App\Exceptions\BaseException;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function returnDefaultUser(): array
    {
        $fakeData = [
            'name' => 'Test User',
            'email' => 'email@email.com',
            'password' => 'password',
            'nationalID' => '123456789',
            'nationality' => 'Testland',
            'countryFlag' => '🏳️',
        ];

        return $fakeData;
    }

    public function test_if_user_has_been_created(): void
    {
        $fakeData = $this->returnDefaultUser();

        $user = (new User)->createUser($fakeData);

        $this->assertDatabaseHas('users', [
            'name' => $fakeData['name'],
            'email' => $fakeData['email'],
        ]);

        $this->assertDatabaseHas('guests', [
            'nationalID' => $fakeData['nationalID'],
            'nationality' => $fakeData['nationality'],
            'countryFlag' => $fakeData['countryFlag'],
            'user_id' => $user->id,
        ]);
    }

    public function test_if_creating_user_with_existent_email_throws_exception(): void
    {
        $fakeData = $this->returnDefaultUser();

        (new User)->createUser($fakeData);

        $this->expectException(BaseException::class);
        (new User)->createUser($fakeData);
    }

    public function test_if_password_is_hashed_when_creating_user(): void
    {
        $fakeData = $this->returnDefaultUser();

        $user = (new User)->createUser($fakeData);

        $this->assertNotEquals($fakeData['password'], $user->password);
        $this->assertTrue(Hash::check($fakeData['password'], $user->password));
    }

    public function test_if_sent_otp_for_reset_password_works(): void
    {
        $fakeData = $this->returnDefaultUser();
        $user = (new User)->createUser($fakeData);

        $otpObject = $user->generateResetPasswordOtp($fakeData['email']);

        $this->assertDatabaseHas('otps', [
            'user_id' => $otpObject->user->id,
            'code' => $otpObject->otp->code,
            'type' => $otpObject->otp->type,
        ]);
    }

    public function test_if_sent_otp_for_reset_password_fails_with_invalid_email(): void
    {
        $user = new User;

        $this->expectException(BaseException::class);
        $this->expectExceptionCode(404);
        $user->generateResetPasswordOtp('random@random.com');
    }

    public function test_if_reset_password_with_otp_works(): void
    {
        $fakeData = $this->returnDefaultUser();
        $user = (new User)->createUser($fakeData);

        $otpObject = $user->generateResetPasswordOtp($fakeData['email']);

        $this->assertDatabaseHas('otps', [
            'user_id' => $otpObject->user->id,
            'code' => $otpObject->otp->code,
            'type' => $otpObject->otp->type,
        ]);

        $newPassword = 'newPassword123';

        $user->resetPasswordWithOtp($fakeData['email'], $otpObject->otp->code, $newPassword);

        $user->refresh();

        $this->assertTrue(Hash::check($newPassword, $user->password));
    }

    public function test_if_reset_password_fails_with_wrong_user(): void
    {
        $fakeData = $this->returnDefaultUser();

        $user = (new User)->createUser($fakeData);

        $otpObject = $user->generateResetPasswordOtp($fakeData['email']);

        $this->assertDatabaseHas('otps', [
            'user_id' => $otpObject->user->id,
            'code' => $otpObject->otp->code,
            'type' => $otpObject->otp->type,
        ]);

        $newPassword = 'newPassword123';

        $this->expectException(BaseException::class);
        $this->expectExceptionCode(404);
        $user->resetPasswordWithOtp('wrongemail', $otpObject->otp->code, $newPassword);
    }

    public function test_if_reset_password_fails_with_wrong_otp(): void
    {
        $fakeData = $this->returnDefaultUser();

        $user = (new User)->createUser($fakeData);

        $otpObject = $user->generateResetPasswordOtp($fakeData['email']);

        $this->assertDatabaseHas('otps', [
            'user_id' => $otpObject->user->id,
            'code' => $otpObject->otp->code,
            'type' => $otpObject->otp->type,
        ]);

        $newPassword = 'newPassword123';

        $this->expectException(BaseException::class);
        $this->expectExceptionCode(403);
        $user->resetPasswordWithOtp($fakeData['email'], 'wrong-code', $newPassword);
    }

    public function test_if_user_can_be_updated_without_password(): void
    {
        $fakeData = $this->returnDefaultUser();
        $user = (new User)->createUser($fakeData);

        $this->actingAs($user);

        Storage::fake('public');
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'email2@email.com',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ];

        $newUser = $user->updateProfile($updateData);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $newUser->name,
            'avatar' => $newUser->avatar,
        ]);

        $this->assertTrue(Hash::check($fakeData['password'], $user->password));
    }

    public function test_if_user_can_be_updated_with_password(): void
    {
        $fakeData = $this->returnDefaultUser();
        $user = (new User)->createUser($fakeData);

        $this->actingAs($user);

        Storage::fake('public');
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'email2@email.com',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
            'password' => 'newPassword123',
        ];

        $newUser = $user->updateProfile($updateData);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $newUser->name,
            'avatar' => $newUser->avatar,
        ]);

        $this->assertTrue(Hash::check($updateData['password'], $user->password));
    }

    public function test_user_has_correct_permissions(): void
    {
        $fakeData = $this->returnDefaultUser();
        $user = (new User)->createUser($fakeData);

        $this->assertInstanceOf(HasManyThrough::class, $user->permissions());

        $roleAdmin = \App\Models\Role::create(['name' => 'admin']);
        $roleGuest = \App\Models\Role::create(['name' => 'guest']);

        $permissionManageUsers = \App\Models\Permission::create(['name' => 'manage_users']);
        $permissionViewRooms = \App\Models\Permission::create(['name' => 'view_rooms']);

        $roleAdmin->permissions()->attach($permissionManageUsers);
        $roleGuest->permissions()->attach($permissionViewRooms);

        $user->roles()->attach($roleGuest);

        $userPermissions = $user->userPermissions();

        $this->assertContains('view_rooms', $userPermissions);
    }
}
