<?php declare(strict_types=1);

namespace App\Service;

use App\Exception\ListingException;
use App\Repository\ListingRepository;
use Ramsey\Uuid\Uuid;

class ListingService extends BaseService
{
    /**
     * @var ListingRepository
     */
    protected $listingRepository;

    public function __construct(ListingRepository $listingRepository)
    {
        $this->listingRepository = $listingRepository;
    }

    public function searchListing($id, $ownerId = 0)
    {
        $listing = $this->listingRepository->checkAndGetListing($id);

        if($ownerId != 0 && $listing['userid'] != $ownerId) {
            throw new ListingException("owner mismatch", 404);
        }

        return $this->listing_db2api($listing);
    }

    public function searchListingByUuid($uuid, $ownerId = 0)
    {
        $listing = $this->listingRepository->searchByUuid($uuid);

        if($ownerId != 0 && $listing['userid'] != $ownerId) {
            throw new ListingException("owner mismatch", 404);
        }

        return $this->listing_db2api($listing);
    }
    public function updateListingState($listingId, $state)
    {
        $fields['listingid'] = $listingId;
        $fields['state'] = $state;

        $listing_db = $this->listingRepository->updateListing($fields);
        return $this->listing_db2api($listing_db);
    }

    public function queryByUser($userId, $page = 1, $perPage = 100)
    {
        $totalCount = $this->listingRepository->countByUser($userId);

        $ret['totalPages'] = ceil($totalCount / $perPage);
        $ret['data'] = [];

        $listings = $this->listingRepository->queryByUser($userId, $page, $perPage);

        foreach ($listings as $listing) {
            $listing_db = get_object_vars($listing);
            $ret['data'][] = $this->listing_db2api($listing_db);
        }

        return $ret;
    }

    public function queryByFilter($filter, $page = 1, $perPage = 100)
    {
        $constraints = $this->listingfilter_api2db($filter);

        // search only published
        $constraints[] = ['state', 'published'];

        $totalCount = $this->listingRepository->countByFilter($constraints);

        $ret['totalPages'] = ceil($totalCount / $perPage);
        $ret['data'] = [];

        $listings = $this->listingRepository->queryByFilter($constraints, $page, $perPage);

        foreach ($listings as $listing) {
            $listing_db = get_object_vars($listing);
            $ret['data'][] = $this->listing_db2api($listing_db);
        }

        return $ret;
    }

    public function addDraftListing($listing_api)
    {
        $listing_db = $this->listing_api2db($listing_api);

        $listing_db['state'] = "draft";
        $listing_db['listingid'] = Uuid::uuid4()->toString();
        $listing_db['created_at'] = date('Y-m-d H:i:s');

        $listing_db = $this->listingRepository->addNewListing($listing_db);
        return $this->listing_db2api($listing_db);
    }

    public function updateListing($listingId, $listing_api)
    {
        $listing_db = $this->listing_api2db($listing_api);

        $listing_db['listingid'] = $listingId;

        $listing_db = $this->listingRepository->updateListing($listing_db);
        return $this->listing_db2api($listing_db);
    }

    public function updateListingImage($listingId, $images)
    {
        $this->listingRepository->clearListingImages($listingId);

        $i = 0;
        foreach($images as $image_uuid) {

            $fields['uuid'] = $image_uuid;
            $fields['listing_id'] = $listingId;
            $fields['order_by'] = $i++;

            $this->listingRepository->updateListingImageByUuid($fields);
        }

        $this->listingRepository->removeUnusedListingImages();
    }

    public function getListingImages($listingId, int $limits)
    {

        $images = $this->listingRepository->getListingImages($listingId, $limits);

        LogService::log("limits=$limits, retcount=" . count($images), "getListingImages");

        $images_ret = [];
        foreach ($images as $image) {
            $image = get_object_vars($image);
            $images_ret[] = $this->listingimage_db2api($image);
        }

        return $images_ret;
    }

    protected function listingimage_db2api($image_db)
    {
        $image_api = [
            'path' => $image_db['file_name'],
            'listingId' => $image_db['listing_id'],
            'uuid' => $image_db['uuid'],
            'orderBy' => $image_db['order_by'],
            'width' => $image_db['width'],
            'height' => $image_db['height'],
        ];
        return $image_api;
    }

    protected function listingfilter_api2db($filter)
    {
        $constraints = [];
        if(isset($filter['authorUuid'])) {
            $constraints[] = ["userid", $filter['authorUuid']];
        }
        if(isset($filter['price'])) {
            $prices = explode(',', $filter['price']);
            if(count($prices) == 2) {
                $constraints[] = ['price', '>', $prices[0]];
                $constraints[] = ['price', '>', $prices[1]];
            }
        }
        if(isset($filter['bounds'])) {
            // not implements
        }
        return $constraints;
    }

    protected function listing_api2db($listing_api)
    {
        $listing_db = [];

        if(isset($listing_api["uuid"])) $listing_db['listingid'] = $listing_api["uuid"];
        if(isset($listing_api["authorId"])) $listing_db['userid'] = $listing_api["authorId"];
        if(isset($listing_api["title"])) $listing_db['listingtitle'] = $listing_api["title"];
        if(isset($listing_api["description"])) $listing_db['listingdescription'] = $listing_api["description"];
        if(isset($listing_api["state"])) $listing_db['state'] = $listing_api["state"];
        if(isset($listing_api["price"]['~#mn'])) {
            $money = $listing_api["price"]['~#mn'];

            if(is_array($money) && count($money) == 2) {
                $listing_db['price'] = $money[0];
//                $listing_db['currency'] = $money[1];
            }
        }
        if(isset($listing_api["publicData"])) $listing_db['publicdata'] = json_encode($listing_api["publicData"]);
//        if(isset($listing_api["publicData"]["category"])) $listing_db['category'] = $listing_api["publicData"]["category"];
//        if(isset($listing_api["publicData"]["rules"])) $listing_db['rules'] = $listing_api["publicData"]["rules"];
//        if(isset($listing_api["publicData"]['location']['address'])) $listing_db['address'] = $listing_api["publicData"]['location']['address'];
//        if(isset($listing_api["publicData"]['location']['building'])) $listing_db['building'] = $listing_api["publicData"]['location']['building'];

        if(isset($listing_api["geolocation"]['~#geo'])) $listing_db['geolocation'] = json_encode($listing_api["geolocation"]['~#geo']);
        if(isset($listing_api['availabilityPlan'])) $listing_db['availability_plan'] = json_encode($listing_api["availabilityPlan"]);
        return $listing_db;
    }

    protected function listing_db2api($listing_db)
    {
        $amenities = [];
        $geolocation = [];
        $publicData = [];

        if(!empty($listing_db['publicdata'])) {
            $publicData = json_decode($listing_db['publicdata'], true);

            if(isset($publicData['amenities']))
                $amenities = $publicData['amenities'];
        }

        if(!empty($listing_db['geolocation'])) {
            $geolocation = json_decode($listing_db['geolocation'], true);
        }

        $listing_api = [
            "uuid" => $listing_db['listingid'],
            "authorId" => $listing_db['userid'],
            "description" => $listing_db['listingdescription'],
            "state" => $listing_db['state'],
            "title" => $listing_db['listingtitle'],
            "price" => [
                '~#mn' => [ $listing_db['price'], 'EUR' ]
            ],
            "publicData" => [
                'amenities' => $amenities,
                "category" => isset($publicData['category'])?$publicData['category']:"",
                "rules" => isset($publicData['rules'])?$publicData['rules']:"",
                "location" => [
                    "address" => isset($publicData['location']['address'])?$publicData['location']['address']:"",
                    "building" => isset($publicData['location']['building'])?$publicData['location']['building']:"",
                ],
            ],
            "geolocation" => $geolocation,
        ];

        if(!empty($listing_db['availability_plan'])) {
            $listing_api['availabilityPlan'] = json_decode($listing_db['availability_plan'], true);
        } else {
            $listing_api['availabilityPlan'] = null;
        }

        return $listing_api;
    }


    //////////////////////////////////////////////////////////
    ///
    public function addAvailabilityException($avail_api)
    {
        $avail_db = $this->availability_api2db($avail_api);

        $avail_db['availabilityexceptionid'] = Uuid::uuid4()->toString();

        $avail_db = $this->listingRepository->addNewAvailabilityException($avail_db);
        return $this->availability_db2api($avail_db);
    }

    public function searchAvailabilityExceptionByUuid($uuid)
    {
        $avail_db = $this->listingRepository->searchAvailabilityExceptionByUuid($uuid);

        return $this->availability_db2api($avail_db);
    }

    public function deleteAvailabilityException($id)
    {
        $this->listingRepository->deleteAvailabilityException($id);
        return "AvailabilityException deleted";
    }


    public function searchAvailabilityException($filter, $page = 1, $perPage = 100)
    {
        $constraints = $this->availabilityfilter_api2db($filter);

        // search only published
        $totalCount = $this->listingRepository->countAvailabilityExceptionByFilter($constraints);

        $ret['totalPages'] = ceil($totalCount / $perPage);
        $ret['data'] = [];

        $avails = $this->listingRepository->queryAvailabilityExceptionByFilter($constraints, $page, $perPage);

        foreach ($avails as $avail) {
            $avail_db = get_object_vars($avail);
            $ret['data'][] = $this->availability_db2api($avail_db);
        }

        return $ret;
    }

    protected function availabilityfilter_api2db($filter_api)
    {
        $constraints = [];
        if(isset($filter_api['listingId'])) {
            $constraints[] = ["listingid", $filter_api['listingId']];
        }

        if(isset($filter_api['start'])) {
            $constraints[] = ["enddate", ">=", $filter_api['start']];
        }

        if(isset($filter['end'])) {
            $constraints[] = ["startdate", "<=", $filter_api['end']];
        }
        return $constraints;
    }

    protected function availability_api2db($avail_api)
    {
        $avail_db = [];
        // if(isset($avail_api['id']))   $avail_db['id'] = $avail_api['id'];
        if(isset($avail_api['uuid']))   $avail_db['availabilityexceptionid'] = $avail_api['uuid'];
        if(isset($avail_api['listingId']))   $avail_db['listingid'] = $avail_api['listingId'];
        if(isset($avail_api['seats']))   $avail_db['seats'] = $avail_api['seats'];
        if(isset($avail_api['start']))   $avail_db['startdate'] = $this->format_time($avail_api['start']);
        if(isset($avail_api['end']))   $avail_db['enddate'] = $this->format_time($avail_api['end']);

        return $avail_db;
    }

    protected function format_time($api)
    {
        $time = strtotime($api);
        return date('Y-m-d 00:00:00', $time);
    }

    protected function availability_db2api($avail_db)
    {
        $avail_api = [
            // 'id' => $avail_db['id'],
            'uuid' => $avail_db['availabilityexceptionid'],
            'listingId' => $avail_db['listingid'],
            'seats' => $avail_db['seats'],
            'start' => $avail_db['startdate'],
            'end' => $avail_db['enddate'],
        ];
        return $avail_api;
    }

    /////////////////////////////////////////////////
    /// functions for message
    ///
    public  function addMessage($msg_api)
    {
        $msg_db = $this->message_api2db($msg_api);

        $msg_db['messageid'] = Uuid::uuid4()->toString();
        $msg_db['created_at'] = date('Y-m-d H:i:s');

        $msg_db = $this->listingRepository->addNewMessage($msg_db);
        return $this->message_db2api($msg_db);
    }


    public function searchMessage($filter, $page = 1, $perPage = 100)
    {
        $constraints[] = ['workplaceid', $filter['transaction_id']];

        // search only published
        $totalCount = $this->listingRepository->countMessageByFilter($constraints);

        $ret['totalPages'] = ceil($totalCount / $perPage);
        $ret['data'] = [];

        $msgs = $this->listingRepository->queryMessageByFilter($constraints, $page, $perPage);

        foreach ($msgs as $msg) {
            $msg_db = get_object_vars($msg);
            $ret['data'][] = $this->message_db2api($msg_db);
        }

        return $ret;
    }

    protected function message_api2db($msg_api)
    {
        $msg_db = [];
//        if(isset($msg_api['id']))   $msg_db['id'] = $msg_api['id'];
        if(isset($msg_api['uuid']))   $msg_db['messageid'] = $msg_api['uuid'];
        if(isset($msg_api['senderId']))   $msg_db['senderid'] = $msg_api['senderId'];
        if(isset($msg_api['customerId']))   $msg_db['customerid'] = $msg_api['customerId'];
        if(isset($msg_api['providerId']))   $msg_db['providerid'] = $msg_api['providerId'];
        if(isset($msg_api['transactionId']))   $msg_db['workplaceid'] = $msg_api['transactionId'];

        if(isset($msg_api['content']))   $msg_db['message'] = $msg_api['content'];
        if(isset($msg_api['createdAt']))   $msg_db['created_at'] = $msg_api['createdAt'];
        if(isset($msg_api['listingId']))   $msg_db['listingid'] = $msg_api['listingId'];

        return $msg_db;
    }

    protected function message_db2api($msg_db)
    {
        $msg_api = [
//            'id' => $msg_db['id'],
            'uuid' => $msg_db['messageid'],
            'senderId' => $msg_db['senderid'],
            'content' => $msg_db['message'],
            'createdAt' => $msg_db['created_at'],
            'listingId' => $msg_db['listingid'],
            'customerId' => $msg_db['customerid'],
            'providerId' => $msg_db['providerid'],
            'transactionId' => $msg_db['workplaceid'],
        ];
        return $msg_api;
    }

    /////////////////////////////

    public function searchReviewByUuid($uuid)
    {
        $review = $this->listingRepository->checkAndGetReview($uuid);
        return $this->review_db2api($review);
    }

    public function searchReview($filter, $page = 1, $perPage = 100)
    {
        if(isset($filter['state']))
            $constraints[] = ['state', $filter['state']];
        if(isset($filter['listingId']))
            $constraints[] = ['listingid', $filter['listingId']];
        if(isset($filter['subjectId']))
            $constraints[] = ['therapistid', $filter['subjectId']];

        LogService::dump($constraints, "constraints", __FUNCTION__);

        // search only published
        $totalCount = $this->listingRepository->countReviewByFilter($constraints);

        $ret['totalPages'] = ceil($totalCount / $perPage);
        $ret['data'] = [];

        $reviews = $this->listingRepository->queryReviewByFilter($constraints, $page, $perPage);

        foreach ($reviews as $review) {
            $review_db = get_object_vars($review);
            $ret['data'][] = $this->review_db2api($review_db);
        }

        LogService::dump($ret, "ret", __FUNCTION__);
        return $ret;
    }

    protected function review_db2api($review_db)
    {
        return [
            "uuid" => $review_db['reviewid'],
            'type' => $review_db['type'],
            'state' => $review_db['state'],
            'rating'=> $review_db['rating'],
            'content'=> $review_db['content'],
            'createdAt' => $review_db['createdate'],
            'authorId' => $review_db['patientid'],
            'subjectId' => $review_db['therapistid'],
        ];
    }

    /////////////////////////////////////////////////
    /// functions for transaction
    /// ////////////////////////////////////////////

    public  function addTransaction($transaction_api)
    {
        $transaction_db = $this->transaction_api2db($transaction_api);

        $transaction_db['transactionid'] = Uuid::uuid4()->toString();
        $transaction_db['created_at'] = date('Y-m-d H:i:s');
        $transaction_db['last_transition'] = $transaction_api['transition'];
        $transaction_db['last_transition_at'] = $transaction_db['created_at'];

        LogService::dump($transaction_db, "transaction_db");

        $transaction_db = $this->listingRepository->addNewTransaction($transaction_db);
        return $this->transaction_db2api($transaction_db);
    }

    public function getTransaction($id)
    {
        $transaction_db = $this->listingRepository->checkAndGetTransaction($id);

        return $this->transaction_db2api($transaction_db);
    }


    public function searchTransaction($filter, $page = 1, $perPage = 100)
    {
        $constraints = [];
        if(isset($filter['providerId'])) $constraints['providerid'] = $filter['providerId'];
        if(isset($filter['customerId'])) $constraints['customerid'] = $filter['customerId'];
        if(isset($filter['lastTransition'])) {
            $constraints['last_transition'] = $filter['lastTransition'];
        }

        // search only published
        $totalCount = $this->listingRepository->countTransactionByFilter($constraints);

        $ret['totalPages'] = ceil($totalCount / $perPage);
        $ret['data'] = [];

        $transactions = $this->listingRepository->queryTransactionByFilter($constraints, $page, $perPage);

        foreach ($transactions as $transaction) {
            $transaction_db = get_object_vars($transaction);
            $ret['data'][] = $this->transaction_db2api($transaction_db);
        }

        return $ret;
    }

    protected function transaction_api2db($transaction_api)
    {
        $transaction_db = [];
//        if(isset($transaction_api['id']))   $transaction_db['id'] = $transaction_api['id'];
        if(isset($transaction_api['uuid']))   $transaction_db['transactionid'] = $transaction_api['uuid'];
        if(isset($transaction_api['params']['listingId']))   $transaction_db['listingid'] = $transaction_api['params']['listingId'];
        if(isset($transaction_api['customerId']))   $transaction_db['customerid'] = $transaction_api['customerId'];
        if(isset($transaction_api['providerId']))   $transaction_db['providerid'] = $transaction_api['providerId'];

        if(isset($transaction_api['createdAt']))   $transaction_db['created_at'] = $transaction_api['createdAt'];
        if(isset($transaction_api['lastTransitionedAt']))   $transaction_db['last_transition_at'] = $transaction_api['lastTransitionedAt'];

        if(isset($transaction_api['lastTransition']))   $transaction_db['last_transition'] = $transaction_api['lastTransition'];

        return $transaction_db;
    }

    protected function transaction_db2api($transaction_db)
    {
        $transaction_api = [
//            'id' => $transaction_db['id'],
            'uuid' => $transaction_db['transactionid'],
//            'listingId' => $transaction_db['listingid'],
            'customerId' => $transaction_db['customerid'],
            'providerId' => $transaction_db['providerid'],

            'createdAt' => $transaction_db['created_at'],
            'lastTransitionedAt' => $transaction_db['last_transition_at'],

            'lastTransition' => $transaction_db['last_transition'],
        ];
        $transaction_api['params']['listingId'] = $transaction_db['listingid'];
        return $transaction_api;
    }


}
