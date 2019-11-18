<?php declare(strict_types=1);

namespace App\Repository;

use App\Exception\UserException;
use App\Service\LogService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Container\ContainerInterface;

class UserRepository
{
    protected $capsule;
    protected $user_table = "user"; // "tb_users";
    protected $listing_image_table = "listing_images";

    public function __construct(ContainerInterface $c)
    {
        $this->capsule = $c->get('db_capsule');
    }

    public function checkAndGetUserById($userId)
    {
        $user = Capsule::table($this->user_table)->where('userid', $userId)->first();
        if (empty($user)) {
            throw new UserException('User not found.', 404);
        }
        return get_object_vars($user);
    }


    public function checkUserByEmail($email)
    {
        $user = Capsule::table($this->user_table)->where('email', $email)->first();

        if (!empty($user)) {
            throw new UserException('Email already exists.', 200);
        }
    }

    public function getUsers(): array
    {
        $users = Capsule::table($this->user_table)->orderBy('creationdate')->get();

        return $users->toArray();
    }

    public function searchUserByEmail($email)
    {
        $user = Capsule::table($this->user_table)->where('email', $email)->first();
        if(empty($user)) {
            throw new UserException('Email not exists.', 204);
        }

        return get_object_vars($user);
    }

    public function searchUsers(string $usersName): array
    {
        $users = Capsule::table($this->user_table)->where('name', 'like', "%$usersName%")->get();

        if (!$users) {
            throw new UserException('User name not found.', 404);
        }

        return $users->toArray();
    }

    public function loginUser(string $email, string $password)
    {
        $user = Capsule::table($this->user_table)->where([['email', $email], ['password', $password]])->first();

        if (empty($user)) {
            throw new UserException('Login failed: Email or password incorrect.', 400);
        }

        return get_object_vars($user);
    }

    public function createUser($user)
    {
        LogService::dump($user, 'user', __FILE__ ,  __LINE__ );
        Capsule::table($this->user_table)->insert($user);

        return $this->checkAndGetUserById($user['userid']);
    }

    public function updateUser($user)
    {
        Capsule::table($this->user_table)->where('userid', $user['userid'])->update($user);

        return $this->checkAndGetUserById($user['userid']);
    }

    public function addListingImage($fields)
    {
        Capsule::table($this->listing_image_table)->insert($fields);

        // get inserted record
        $record = Capsule::table($this->listing_image_table)->where('uuid', $fields['uuid'])->first();
        if(empty($record)) {
            throw new UserException("adding listing image failed", 404);
        }
        return get_object_vars($record);
    }

    public function deleteUser($userId): string
    {
        Capsule::table($this->user_table)->where('userid', $userId)->delete();

        return 'The user was deleted.';
    }

    public function changePassword($userId, $newPwd)
    {
        Capsule::table($this->user_table)->where('userid', $userId)->update(['password' => $newPwd]);

        return $this->checkAndGetUserById($userId);
    }

    public function changeEmail($userId, $newEmail)
    {
        LogService::log("id=$userId, newEmail=$newEmail", __FILE__, __LINE__);

        Capsule::table($this->user_table)->where('userid', $userId)->update(['email' => $newEmail, 'username'=>$newEmail]);

        return $this->checkAndGetUserById($userId);
    }

    public function pendingChangeEmail($userId, $newEmail)
    {
        Capsule::table($this->user_table)->where('userid', $userId)->update(['pending_email' => $newEmail]);

        return $this->checkAndGetUserById($userId);
    }
}
