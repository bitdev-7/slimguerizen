<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 6/22/2019
 * Time: 12:35 AM
 */

namespace App\Controller\Messages;


use App\Controller\BaseController;
use App\Service\ListingService;
use App\Service\LogService;
use App\Service\UserService;
use Slim\Container;

class BaseMessage extends BaseController
{
    protected function buildMessageResource($message, $includes)
    {
        $data = ["^ ",
            '~:id', '~u' . $message['uuid'],
            '~:type', '~:message',
            '~:attributes', ["^ ",
                "~:content", $message['content'],
                "~:createdAt", '~t' . $this->formatTime($message['createdAt']),
            ],
            '~:relationships', ["^ ",

            ],
        ];

        $included = [];

        if(!empty($includes)) {
            $include_arr = explode(',', $includes);
            if (in_array('sender', $include_arr)) {
                $user = $this->getUserService()->getUser($message['senderId']);

                // entry for relationships
                $data[8][] = "~:sender";
                $data[8][] = ["^ ",
                    "~:data", ["^ ",
                        "~:id", "~u" . $user['uuid'],
                        "~:type", "~:user",
                    ]
                ];
                // add to included
                $included[] = $this->buildUserResource($user);
            }

            if (in_array('sender.profileImage', $include_arr)) {

            }
        }

        return ['data'=>$data, 'included'=>$included];;
    }

    protected function buildUserResource($user)
    {
        $abbreviatedName = "";
        if(strlen($user['firstName']) > 0)
            $abbreviatedName .= $user['firstName'][0];
        if(strlen($user['lastName']) > 0)
            $abbreviatedName .= $user['lastName'][0];

        $resource = ["^ ",
            "~:id", "~u" . $user['uuid'],
            "~:type", "~:user",
            "~:attributes", ["^ ",
                "~:banned", false,
                "~:deleted", false,
                "~:profile", ["^ ",
                    "~:displayName", $user['displayName'],
                    "~:abbreviatedName", $abbreviatedName,
                    "~:bio", $user['bio'],
                ],
            ],
        ];
        return $resource;
    }

}