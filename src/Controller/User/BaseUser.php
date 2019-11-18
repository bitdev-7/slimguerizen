<?php declare(strict_types=1);

namespace App\Controller\User;

use App\Controller\BaseController;
use App\Exception\UserException;
use App\Service\LogService;
use App\Service\MailService;
use App\Service\UserService;
use Slim\Container;
use Ramsey\Uuid\Uuid;
use Illuminate\Database\Capsule\Manager as Capsule;

abstract class BaseUser extends BaseController
{
    /**
     * @throws UserException
     */
    protected function checkUserPermissions()
    {
        $input = $this->getInput();
        if ($this->args['id'] != $input['decoded']->sub) {
            throw new UserException('User permission failed.', 400);
        }
    }

    protected function appendIncludeResource($resource, $request, $user)
    {
        // get profile
        $includes = $request->getParam('include');
        if(empty($includes)) {
            return $resource;
        }

        $arr_include = explode(',', $includes);

        if(in_array('profileImage', $arr_include)) {
            $path = $this->getUserService()->getImageFilename($user['uuid']);
            if(empty($path)) {
                return $resource;
            }

            $baseUri = strstr($request->getUri()->__toString(), $request->getUri()->getPath(), true);
            $url = $baseUri . "/image?f=" . $path;

            $uuid_image = Uuid::uuid4()->toString();

            $resource[2][] = "~:relationships";
            $resource[2][] = ["^ ",
                "~:profileImage", ["^ ",
                    "~:data", ["^ ",
                        "~:id", "~u" . $uuid_image,
                        "~:type", "~:image",
                    ]
                ]
            ];

            $res_include = ["~:included", [[ "^ ",
                "~:id","~u" . $uuid_image,
                "~:type","~:image",
                "~:attributes",["^ ",
                    "~:variants",["^ ",
                        "~:square-small2x",["^ ",
                            "~:height",480,
                            "~:width",480,
                            "~:url", "$url&w=480&h=480",
                            "~:name","square-small2x"
                        ],
                        "~:square-small",["^ ",
                            "~:height",240,
                            "~:width",240,
                            "~:url", "$url&w=240&h=240",
                            "~:name","square-small"
                        ]
                    ]
                ]
            ]]];
            $resource = array_merge($resource, $res_include);
        }

        return $resource;
    }

    protected function buildUserResource($user, $request)
    {
        $abbreviatedName = "";
        if(strlen($user['firstName']) > 0)
            $abbreviatedName .= $user['firstName'][0];
        if(strlen($user['lastName']) > 0)
            $abbreviatedName .= $user['lastName'][0];

        $resource = [
            '^ ',
            '~:data', ["^ ",
                '~:id', '~u' . $user['uuid'],
                '~:type', '~:currentUser',
                '~:attributes', ["^ ",
                    "~:banned", false,
                    "~:deleted", false,
                    "~:email", $user['email'],
                    "~:emailVerified", ($user['emailVerified']==0)?false:true,
                    "~:pendingEmail", $user['pendingEmail'],
                    "~:description", $user['bio'],

                    '~:profile', ["^ ",
                        "~:firstName", $user['firstName'],
                        "~:lastName", $user['lastName'],
                        "~:displayName", $user['displayName'],
                        "~:bio", $user['bio'],
                        "~:abbreviatedName", $abbreviatedName,
                        "~:publicData", ["^ ",],
                        "~:protectedData", ["^ ",
                            "~:phoneNumber", isset($user['protectedData']['phoneNumber'])?$user['protectedData']['phoneNumber']:"",
                        ],
                        "~:privateData", ["^ ",],
                    ],
                ],
            ],
        ];

        return $this->appendIncludeResource($resource, $request, $user);
    }

    protected function buildPublicResource($user, $request)
    {
        LogService::dump($user, "user_to_dump", __FUNCTION__);

        $abbreviatedName = "";
        if(strlen($user['firstName']) > 0)
            $abbreviatedName .= $user['firstName'][0];
        if(strlen($user['lastName']) > 0)
            $abbreviatedName .= $user['lastName'][0];

        $resource = [
            '^ ',
            '~:data', ["^ ",
                '~:id', '~u' . $user['uuid'],
                '~:type', '~:user',
                '~:attributes', ["^ ",
                    "~:banned", false,
                    "~:deleted", false,
                    "~:createdAt", "~t" . $this->formatTime($user['createdAt']),
                    '~:profile', ["^ ",
                        "~:displayName", $user['displayName'],
                        "~:bio", $user['bio'],
                        "~:abbreviatedName", $abbreviatedName,
                        "~:publicData", ["^ ",
                            "~:email_verified_flag",$user['email_verified_flag'],
                            "~:num_bookings",0,
                        ],
                    ],
                ],
            ],
        ];

        return $this->appendIncludeResource($resource, $request, $user);
    }

    protected function updateAccessToken($token)
    {
        $capsule = $this->container->get('db_capsule');
        $config = $this->container->get('settings')['auth_server'];

        $fields['user_id'] = $token['user_id'];

        Capsule::table($config['access_token_table'])->where('access_token', $token['access_token'])->update($fields);

        $token_updated = Capsule::table($config['access_token_table'])->where('access_token', $token['access_token'])->first();
        if (empty($token_updated)) {
            throw new UserException('Invalid token.', 404);
        }
        return get_object_vars($token_updated);
    }

}
