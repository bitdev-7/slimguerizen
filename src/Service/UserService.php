<?php declare(strict_types=1);

namespace App\Service;

use App\Exception\UserException;
use App\Repository\UserRepository;
use \Firebase\JWT\JWT;
use Ramsey\Uuid\Uuid;

class UserService extends BaseService
{
    /**
     * @var UserRepository
     */
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    protected function checkAndGetUser($userId)
    {
        return $this->userRepository->checkAndGetUserById($userId);
    }

    public function getUsers(): array
    {
        return $this->userRepository->getUsers();
    }

    public function getUser($userId)
    {
        $user_db = $this->checkAndGetUser($userId);
        return $this->user_db2api($user_db);
    }

    public function getUserByEmail($email)
    {
        $user_db = $this->userRepository->searchUserByEmail($email);

        return $this->user_db2api($user_db);
    }

    public function getUserByUuid($uuid)
    {
        $user_db = $this->userRepository->checkAndGetUserById($uuid);
        return $this->user_db2api($user_db);
    }

    public function searchUsers(string $usersName): array
    {
        return $this->userRepository->searchUsers($usersName);
    }

    protected function user_api2db($userApi)
    {
        $userDb = [];
        // if(isset($userApi['id'])) $userDb['id'] = $userApi->id;

        if(isset($userApi['email'])) $userDb['email'] = self::validateEmail($userApi['email']);
        if(isset($userApi['password'])) $userDb['password'] = sha1($userApi['password']);
        if(isset($userApi['firstName'])) $userDb['firstname'] = $userApi['firstName'];
        if(isset($userApi['lastName'])) $userDb['lastname'] = $userApi['lastName'];

        if(isset($userApi['displayName'])) $userDb['display_name'] = $userApi['displayName'];
        if(isset($userApi['bio'])) $userDb['description'] = $userApi['bio'];
        if(isset($userApi['protectedData'])) {
            $userDb['protecteddata'] = json_encode($userApi['protectedData']);
            // if(isset($userApi['protectedData']['phoneNumber'])) $userDb['phone_number'] = $userApi['protectedData']['phoneNumber'];
        }

        return $userDb;
    }

    protected function user_db2api($userDb)
    {
        // $retObj['id'] = $userDb['id'];
        $retObj['uuid'] = $userDb['userid'];
        $retObj['email'] = $userDb['email'];
        $retObj['firstName'] = $userDb['firstname'];
        $retObj['lastName'] = $userDb['lastname'];
        $retObj['displayName'] = $userDb['display_name'];
        if(isset($userDb['protecteddata'])) {
            $retObj['protectedData'] = json_decode($userDb['protecteddata'], true);
        }

        $retObj['bio'] = $userDb['description'];
        $retObj['emailVerified'] = $userDb['email_verified'];
        $retObj['pendingEmail'] = $userDb['pending_email'];
        $retObj['createdAt'] = $userDb['creationdate'];
        $retObj['email_verified_flag'] = $userDb['email_verified'];
        return $retObj;
    }

    public function createUser($input)
    {
        if (!isset($input['email'])) {
            throw new UserException('The field "email" is required.', 204);
        }
        if (!isset($input['password'])) {
            throw new UserException('The field "password" is required.', 204);
        }

        $user = $this->user_api2db($input);

        // additional fields for new user
        $uuid = Uuid::uuid4()->toString();

        $user = array_merge([
            'userid' => $uuid,
            'username' => $user['email'],
            'display_name' => $user['firstname'] . '. ' . $user['lastname'][0],
            'scope' => 'user',
            'email_verified' => 0,
            'creationdate' => date('Y-m-d H:i:s'),
            ], $user);

        LogService::dump($user, "user1");
        // check email duplication
        $this->userRepository->checkUserByEmail($user['email']);

        LogService::dump($user, "user2");

        $new_user = $this->userRepository->createUser($user);

        LogService::dump($new_user, "user3");

        return $this->user_db2api($new_user);
    }

    public function updateUser(array $input, $userId)
    {
        // build db_fields
        $user_updated = $this->user_api2db($input);

        // set id to update
        $user_updated['userid'] = $userId;
//        $user_updated['updated_at'] = date('Y-m-d H:i:s');

        $updated_user = $this->userRepository->updateUser($user_updated);
        return $this->user_db2api($updated_user);
    }

    public function deleteUser(int $userId): string
    {
        $this->checkAndGetUser($userId);

        return $this->userRepository->deleteUser($userId);
    }

    public function loginUser(array $input): string
    {
        $password = sha1($input['password']);
        $user = $this->userRepository->loginUser($input['email'], $password);
        $token = array(
            'sub' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'iat' => time(),
            'exp' => time() + (7 * 24 * 60 * 60),
        );

        return JWT::encode($token, getenv('SECRET_KEY'));
    }

    public function changePasswordByEmail($email, $oldPwd, $newPwd)
    {
        $user_db = $this->userRepository->loginUser($email, sha1($oldPwd));

        $this->userRepository->changePassword($user_db['userid'], sha1($newPwd));
        return $this->user_db2api($user_db);
    }

    public function changeEmail($email, $pwd, $newEmail)
    {
        self::validateEmail($newEmail);

        $user = $this->userRepository->loginUser($email, sha1($pwd));

        LogService::dump($user, "user");

        if($user['email_verified'] == 0) {
            $user_db = $this->userRepository->changeEmail($user['userid'], $newEmail);
        } else {
            $user_db = $this->userRepository->pendingChangeEmail($user['userid'], $newEmail);
        }

        return $this->user_db2api($user_db);
    }

    public function makeResetPasswordToken($email)
    {
        $user = $this->userRepository->searchUserByEmail($email);

        $fields['userid'] = $user['userid'];
        $fields['reset_password_token'] = Uuid::uuid4()->toString();
        // $fields['reset_password_sent_at'] = date('Y-m-d H:i:s');

        $user = $this->userRepository->updateUser($fields);
        return $user['reset_password_token'];
    }

    public function resetPassword($email, $token, $newPwd)
    {
        $user = $this->userRepository->searchUserByEmail($email);
        if($user['reset_password_token'] != $token) {
            throw new UserException('The password reset token is invalid or does not match the given email address.', 403);
        }

        $fields['userid'] = $user['userid'];
        $fields['password'] = sha1($newPwd);
        $fields['reset_password_token'] = '';
        // $fields['reset_password_sent_at'] = '';

        $this->userRepository->updateUser($fields);
    }

    public function makeVerifyEmailToken($userId)
    {
        $fields['userid'] = $userId;
        $fields['verify_email_token'] = Uuid::uuid4()->toString();
        $user = $this->userRepository->updateUser($fields);
        return $user['verify_email_token'];
    }

    public function verifyEmail($userId, $token)
    {
        $user = $this->userRepository->checkAndGetUserById($userId);

        if($user['verify_email_token'] != $token) {
            throw new UserException('The email verification token is invalid or has expired.', 403);
        }

        $fields['userid'] = $user['userid'];

        if($user['email_verified'] == 0) {
            $fields['email_verified'] = 1;
        } else if(!empty($user['pending_email'])){
            $fields['email'] = $user['pending_email'];
            $fields['username'] = $user['pending_email'];
        }
        $fields['pending_email'] = '';
        $fields['verify_email_token'] = '';

        $user_db = $this->userRepository->updateUser($fields);
        return $this->user_db2api($user_db);
    }

    public function updateImage($userId, $fileInfo)
    {
        $fields['userid'] = $userId;
        $fields['image_file_name'] = $fileInfo['path'];
//        $fields['image_content_type'] = $fileInfo['contentType'];
//        $fields['image_file_size'] = $fileInfo['size'];
//        $fields['image_updated_at'] = date('Y-m-d H:i:s');

        $this->userRepository->updateUser($fields);
    }

    public function addListingImage($fileInfo)
    {
        $fields['uuid'] = Uuid::uuid4()->toString();
        $fields['file_name'] = $fileInfo['path'];
        $fields['width'] = $fileInfo['width'];
        $fields['height'] = $fileInfo['height'];

        $fields = $this->userRepository->addListingImage($fields);

        return $fields['uuid'];
    }

    public function getImageFilename($userId)
    {
        $user = $this->userRepository->checkAndGetUserById($userId);
        return $user['image_file_name'];
    }

}
