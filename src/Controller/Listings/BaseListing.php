<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 6/22/2019
 * Time: 12:35 AM
 */

namespace App\Controller\Listings;


use App\Controller\BaseController;
use App\Service\ListingService;
use App\Service\LogService;
use App\Service\UserService;
use Slim\Container;

class BaseListing extends BaseController
{
    protected function buildListingResource($listing, $includes)
    {
        $data = ["^ ",
            '~:id', '~u' . $listing['uuid'],
            '~:type', '~:ownListing',
            '~:attributes', ["^ ",
                "~:description", $listing['description'],
                "~:deleted", false,
                "~:state", $listing['state'],
                "~:title", $listing['title'],
                "~:geolocation", ["~#geo", $listing['geolocation'] ],
                "~:availabilityPlan", $listing['availabilityPlan'],
                '~:publicData', ["^ ",
                    "~:amenities", $listing['publicData']['amenities'],
                    "~:category", $listing['publicData']['category'],
                    "~:location", ["^ ",
                        "~:address", $listing['publicData']['location']['address'],
                        "~:building", $listing['publicData']['location']['building'],
                    ],
                    "~:rules", $listing['publicData']['rules'],
                ],
                '~:privateData', ["^ ",
                ],
                '~:price', ['~#mn', $listing['price']['~#mn']],
            ],
            '~:relationships', ["^ ",

            ],
        ];

        $included = [];
        // return ['data'=>$data, 'included'=>$included];

        if(!empty($includes)) {
            $include_arr = explode(',', $includes);
            if(in_array('author', $include_arr)) {
                $user = $this->getUserService()->getUser($listing['authorId']);

                // entry for relationships
                $data[8][] = "~:author";
                $data[8][] = ["^ ",
                    "~:data", ["^ ",
                        "~:id", "~u" . $user['uuid'],
                        "~:type", "~:user",
                    ]
                ];
                // add to included
                $included[] = $this->buildUserResource($user);
            }
            if(in_array('images', $include_arr)) {

                $limits = $this->request->getQueryParam('limit_images', 0);

                $images = $this->getListingService()->getListingImages($listing['uuid'], (int)$limits);

                LogService::dump($images, "images", __CLASS__);

                if(count($images) >= 1) {

                    $img_entries = [];
                    foreach ($images as $image) {
                        $img_entries[] = ["^ ",
                            "~:id", "~u" . $image['uuid'],
                            "~:type", "~:image",
                        ];
                        $included[] = $this->buildImageResource($image);
                    }
                    // entry for relationships
                    $data[8][] = "~:images";
                    $data[8][] = ["^ ",
                        "~:data", $img_entries
                    ];
                }
            }
        }

        return ['data'=>$data, 'included'=>$included];
    }


    protected function buildPublicListingResource($listing, $includes)
    {
        $data = ["^ ",
            '~:id', '~u' . $listing['uuid'],
            '~:type', '~:listing',
            '~:attributes', ["^ ",
                "~:description", $listing['description'],
                "~:deleted", false,
                "~:state", $listing['state'],
                "~:title", $listing['title'],
                "~:geolocation", ["~#geo", $listing['geolocation'] ],
                '~:publicData', ["^ ",
                    "~:amenities", $listing['publicData']['amenities'],
                    "~:category", $listing['publicData']['category'],
                    "~:location", ["^ ",
                        "~:address", $listing['publicData']['location']['address'],
                        "~:building", $listing['publicData']['location']['building'],
                    ],
                    "~:rules", $listing['publicData']['rules'],
                ],
                '~:price', ['~#mn', $listing['price']['~#mn']],
            ],
            '~:relationships', ["^ ",

            ],
        ];

        $included = [];

        // return ['data'=>$data, 'included'=>$included];

        if(!empty($includes)) {
            $include_arr = explode(',', $includes);
            if(in_array('author', $include_arr)) {
                $user = $this->getUserService()->getUser($listing['authorId']);

                // entry for relationships
                $data[8][] = "~:author";
                $data[8][] = ["^ ",
                    "~:data", ["^ ",
                        "~:id", "~u" . $user['uuid'],
                        "~:type", "~:user",
                    ]
                ];
                // add to included
                $included[] = $this->buildUserResource($user);
            }
            if(in_array('images', $include_arr)) {

                $limits = $this->request->getQueryParam('limit_images', 0);

                $images = $this->getListingService()->getListingImages($listing['uuid'], (int)$limits);

                LogService::dump($images, "images", __CLASS__);

                if(count($images) >= 1) {

                    $img_entries = [];
                    foreach ($images as $image) {
                        $img_entries[] = ["^ ",
                            "~:id", "~u" . $image['uuid'],
                            "~:type", "~:image",
                        ];
                        $included[] = $this->buildImageResource($image);
                    }
                    // entry for relationships
                    $data[8][] = "~:images";
                    $data[8][] = ["^ ",
                        "~:data", $img_entries
                    ];
                }
            }
        }

        return ['data'=>$data, 'included'=>$included];
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

    protected function buildImageResource($image)
    {
        $url = $this->baseUrl . "/image?f=" . $image['path'];

        $resource = ["^ ",
            "~:id", "~u" . $image['uuid'],
            "~:type", "image",
            "~:attributes", ["^ ",
                "~:variants",["^ ",
                    "~:landscape-crop2x",["^ ",
                        "~:height",533,
                        "~:width",800,
                        "~:url", "$url&w=800&h=533",
                        "~:name","landscape-crop2x"
                    ],
                    "~:landscape-crop",["^ ",
                        "~:height",267,
                        "~:width",400,
                        "~:url", "$url&w=400&h=267",
                        "~:name","landscape-crop"
                    ]
                ]
            ]
        ];
        return $resource;
    }
}