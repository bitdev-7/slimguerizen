<?php
/**
 * Created by PhpStorm.
 * User: Home
 * Date: 7/29/2019
 * Time: 6:43 PM
 */

namespace App\Controller\Transactions;


use App\Controller\BaseController;

class BaseTransaction extends BaseController
{
    protected function buildTransactionResource($transaction, $includes = "")
    {
        $data = ["^ ",
            '~:id', '~u' . $transaction['uuid'],
            '~:type', '~:transaction',
            '~:attributes', ["^ ",
                "~:createdAt", "~t" . $this->formatTime($transaction['createdAt']),
                "~:processName", "preauth-with-nightly-booking",
                "~:lastTransition", $transaction['lastTransition'],
                "~:lastTransitionedAt", "~t" . $this->formatTime($transaction['lastTransitionedAt']),
            ],
            '~:relationships', ["^ ",

            ],
        ];

        $included = [];

        if(!empty($includes)) {
            $include_arr = explode(',', $includes);

            if(in_array('customer', $include_arr)) {
                $user = $this->getUserService()->getUser($transaction['customerId']);
                // entry for relationships
                $data[8][] = "~:customer";
                $data[8][] = ["^ ",
                    "~:data", ["^ ",
                        "~:id", "~u" . $user['uuid'],
                        "~:type", "~:user",
                    ]
                ];
                // add to included
                $included[] = $this->buildUserResource($user);
            }

            if(in_array('provider', $include_arr)) {
                $user = $this->getUserService()->getUser($transaction['providerId']);
                // entry for relationships
                $data[8][] = "~:provider";
                $data[8][] = ["^ ",
                    "~:data", ["^ ",
                        "~:id", "~u" . $user['uuid'],
                        "~:type", "~:user",
                    ]
                ];
                // add to included
                $included[] = $this->buildUserResource($user);
            }

            if(in_array('customer.profileImage', $include_arr)) {

            }

            if(in_array('provider.profileImage', $include_arr)) {

            }

            if(in_array('listing', $include_arr)) {
                $listing = $this->getListingService()->searchListing($transaction['params']['listingId']);

                // entry for relationships
                $data[8][] = "~:listing";
                $data[8][] = ["^ ",
                    "~:data", ["^ ",
                        "~:id", "~u" . $listing['uuid'],
                        "~:type", "~:listing",
                    ]
                ];
                // add to included
                $included[] = $this->buildListingResource($listing);
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

    protected function buildListingResource($listing)
    {
        $resource = ["^ ",
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
        return $resource;
    }

}