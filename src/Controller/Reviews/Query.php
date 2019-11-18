<?php declare(strict_types=1);

namespace App\Controller\Reviews;

use App\Controller\BaseController;
use App\Exception\UserException;
use App\Service\LogService;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;

class Query extends BaseController
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);

        $input = $this->request->getQueryParams();

        $constraint = [];
        if(isset($input['listing_id']))
            $constraint['listingId'] = $input['listing_id'];

        if(isset($input['state']))
            $constraint['state'] = $input['state'];

        if(isset($input['subject_id']))
            $constraint['subjectId'] = $input['subject_id'];

        $perPage = (int)$this->request->getQueryParam('per_page', 100);
        $page = (int)$this->request->getQueryParam('page', 1);
        if($page <= 0 || $perPage <= 0) {
            throw new ListingException("invalid pagination param", 404);
        }

        $reviews = $this->getListingService()->searchReview($constraint, $page, $perPage);

        $resp = [
            "^ ",
            "~:data", [],
            "~:included", [],
            "~:meta", [ "^ ",
                "~:totalItems", count($reviews['data']),
                "~:totalPages", $reviews['totalPages'],
                "~:page", $page,
                "~:perPage", $perPage,
            ],
        ];

        $includes = $request->getQueryParam('include');

        foreach ($reviews['data'] as $review) {
            $resource = $this->buildReviewResource($review, $includes);
            $resp[2][] = $resource['data'];

            foreach ($resource['included'] as $included) {
                $resp[4][] = $included;
            }
        }

        return $this->response->withJson($resp, 200, JSON_PRETTY_PRINT);
    }


    public function showReview(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);

        $input = $this->request->getParams();

        $review = $this->getListingService()->searchReviewByUuid($input['id']);

        LogService::dump($review, "review", "showReview");

        // build response
        $response = [
            '^ ',
            '~:data', [],
            '~:included', [],
        ];

        $includes = $request->getQueryParam('include');
        $resource = $this->buildReviewResource($review, $includes);

        $response[2] = $resource['data'];
        foreach ($resource['included'] as $included) {
            $response[4][] = $included;
        }

        return $this->response->withJson($response, 200, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    protected function buildReviewResource($review, $includes)
    {
        $data = ["^ ",
            '~:id', '~u' . $review['uuid'],
            '~:type', '~:review',
            '~:attributes', ["^ ",
                "~:type", $review['type'],
                "~:state", $review['state'],
                "~:rating", ($review['state']=="public")?$review['rating']:null,
                "~:content", ($review['state']=="public")?$review['content']:null,
                "~:createdAt", '~t' . $this->formatTime($review['createdAt']),
            ],
            '~:relationships', ["^ ",

            ],
        ];

        $included = [];
        // return ['data'=>$data, 'included'=>$included];
        if(!empty($includes)) {
            $include_arr = explode(',', $includes);
            if(in_array('author', $include_arr)) {
                try {
                    LogService::dump($review, "review", __FUNCTION__, __LINE__);

                    $user = $this->getUserService()->getUser($review['authorId']);

                    LogService::dump($user, "user", __FUNCTION__, __LINE__);

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
                } catch(UserException $e) {

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
}
