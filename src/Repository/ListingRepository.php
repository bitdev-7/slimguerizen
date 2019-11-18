<?php declare(strict_types=1);

namespace App\Repository;

use App\Exception\ListingException;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Container\ContainerInterface;

class ListingRepository extends BaseRepository
{
    protected $capsule;
    protected $listing_table = 'listing';    // 'tb_listings';
    protected $listing_image_table = 'listing_images';
    protected $availability_exception_table = 'availability_exception';
    protected $message_table = 'message';
    protected $review_table = 'review';
    protected $transaction_table = 'transaction';

    public function __construct(ContainerInterface $c)
    {
        $this->capsule = $c->get('db_capsule');
    }

    public function checkAndGetListing($listingId)
    {
        $listing = Capsule::table($this->listing_table)->where('listingid', $listingId)->first();
        if (empty($listing)) {
            throw new ListingException('Listing not found.', 404);
        }
        return get_object_vars($listing);
    }

    public function searchByUuid($uuid)
    {
        $listing = Capsule::table($this->listing_table)->where('listingid', $uuid)->first();
        if (empty($listing)) {
            throw new ListingException('Listing not found.', 404);
        }
        return get_object_vars($listing);
    }

    public function addNewListing($listing)
    {
        Capsule::table($this->listing_table)->insert($listing);

        return $this->checkAndGetListing($listing['listingid']);
    }

    public function updateListing($listing)
    {
        Capsule::table($this->listing_table)->where('listingid', $listing['listingid'])->update($listing);

        return $this->checkAndGetListing($listing['listingid']);
    }

    public function updateListingImageByUuid($fields)
    {
        Capsule::table($this->listing_image_table)->where('uuid', $fields['uuid'])->update($fields);

        // checking
        $img = Capsule::table($this->listing_image_table)->where('uuid', $fields['uuid'])->first();
        if(empty($img)) {
            throw new ListingException("listing_image not found", 404);
        }
        return get_object_vars($img);
    }

    public function clearListingImages($listingId)
    {
        Capsule::table($this->listing_image_table)->where('listing_id', $listingId)->update(['listing_id'=>""]);
    }

    public function removeUnusedListingImages()
    {
        Capsule::table($this->listing_image_table)->where('listing_id', "")->delete();
    }

    public function getListingImages($listingId, int $limits)
    {
        if($limits == 0) {
            $images = Capsule::table($this->listing_image_table)->where('listing_id', $listingId)->orderBy('order_by')->get();
        } else {
            $images = Capsule::table($this->listing_image_table)->where('listing_id', $listingId)->orderBy('order_by')->limit($limits)->get();
        }

        return $images;
    }

    public function countByUser($userId)
    {
        return Capsule::table($this->listing_table)->where('userid', $userId)->count();
    }

    public function countByFilter($constraints)
    {
        return Capsule::table($this->listing_table)->where($constraints)->count();
    }

    public function queryByUser($userId, $page, $perPage)
    {
        $skip = ($page - 1 ) * $perPage;
        return Capsule::table($this->listing_table)->where('userid', $userId)->orderByDesc('created_at')->skip($skip)->limit($perPage)->get();
    }

    public function queryByFilter($constraints, $page, $perPage)
    {
        $skip = ($page - 1 ) * $perPage;
        return Capsule::table($this->listing_table)->where($constraints)->orderByDesc('created_at')->skip($skip)->limit($perPage)->get();
    }


    //////////////////////////////////////////

    public function checkAndGetAvailabilityException($availId)
    {
        $avail = Capsule::table($this->availability_exception_table)->where('availabilityexceptionid', $availId)->first();
        if (empty($avail)) {
            throw new ListingException('AvailabilityException not found.', 404);
        }
        return get_object_vars($avail);
    }

    public function addNewAvailabilityException($avail)
    {
        Capsule::table($this->availability_exception_table)->insert($avail);

        return $this->checkAndGetAvailabilityException($avail['availabilityexceptionid']);
    }

    public function searchAvailabilityExceptionByUuid($uuid)
    {
        $avail = Capsule::table($this->availability_exception_table)->where('availabilityexceptionid', $uuid)->first();
        if (empty($avail)) {
            throw new ListingException('AvailabilityException not found.', 404);
        }
        return get_object_vars($avail);
    }

    public function deleteAvailabilityException($id)
    {
        Capsule::table($this->availability_exception_table)->where('availabilityexceptionid', $id)->delete();
    }

    public function countAvailabilityExceptionByFilter($constraints)
    {
        return Capsule::table($this->availability_exception_table)->where($constraints)->count();
    }

    public function queryAvailabilityExceptionByFilter($constraints, $page, $perPage)
    {
        $skip = ($page - 1 ) * $perPage;
        return Capsule::table($this->availability_exception_table)->where($constraints)->orderBy('startdate')->skip($skip)->limit($perPage)->get();
    }

    /////////////////////////////////////////////////////////
    /// for message
    ///
    public function addNewMessage($message)
    {
        Capsule::table($this->message_table)->insert($message);

        return $this->checkAndGetMessage($message['messageid']);
    }

    public function checkAndGetMessage($id)
    {
        $msg = Capsule::table($this->message_table)->where('messageid', $id)->first();
        if(empty($msg)) {
            throw new ListingException("message not found", 404);
        }
        return get_object_vars($msg);
    }


    public function countMessageByFilter($constraints)
    {
        return Capsule::table($this->message_table)->where($constraints)->count();
    }

    public function queryMessageByFilter($constraints, $page, $perPage)
    {
        $skip = ($page - 1 ) * $perPage;
        return Capsule::table($this->message_table)->where($constraints)->orderByDesc('created_at')->skip($skip)->limit($perPage)->get();
    }

    ////////////////////////////////////////////

    public function checkAndGetReview($id)
    {
        $review = Capsule::table($this->review_table)->where('reviewid', $id)->first();
        if(empty($review)) {
            throw new ListingException("review not found", 404);
        }
        return get_object_vars($review);
    }

    public function countReviewByFilter($constraints)
    {
        return Capsule::table($this->review_table)->where($constraints)->count();
    }

    public function queryReviewByFilter($constraints, $page, $perPage)
    {
        $skip = ($page - 1 ) * $perPage;
        return Capsule::table($this->review_table)->where($constraints)->orderByDesc('createdate')->skip($skip)->limit($perPage)->get();
    }

    //////////////////////////////////////////
    /// apis for transaction
    ///

    public function addNewTransaction($transaction)
    {
        Capsule::table($this->transaction_table)->insert($transaction);

        return $this->checkAndGetTransaction($transaction['transactionid']);
    }

    public function checkAndGetTransaction($id)
    {
        $transaction = Capsule::table($this->transaction_table)->where('transactionid', $id)->first();
        if(empty($transaction)) {
            throw new ListingException("transaction not found", 404);
        }
        return get_object_vars($transaction);
    }


    public function countTransactionByFilter($constraints)
    {
        return Capsule::table($this->transaction_table)->where($constraints)->count();
    }

    public function queryTransactionByFilter($constraints, $page, $perPage)
    {
        $skip = ($page - 1 ) * $perPage;
        return Capsule::table($this->transaction_table)->where($constraints)->orderByDesc('created_at')->skip($skip)->limit($perPage)->get();
    }


}
