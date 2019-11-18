<?php declare(strict_types=1);

namespace App\Controller\User;

use App\Exception\UserException;
use App\Service\LogService;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Exception\ImageException;
use Intervention\Image\Exception\NotSupportedException;
use Intervention\Image\ImageManager;
use Slim\Http\Request;
use Slim\Http\Response;

class Image extends BaseUser
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);
        $input = $request->getParams();

        $width = isset($input['w'])?$input['w']:0;
        $height = isset($input['h'])?$input['h']:0;

        $manager = new ImageManager();
        $path = $_SERVER["DOCUMENT_ROOT"] . "/../upload/" . $input['f'];

        LogService::log("here");

        try {
            $image = $manager->make($path);
        } catch(ImageException $e) {
            throw new UserException($e->getMessage(), 400);
        }

        if($width != 0 && $height != 0) {
            $scale = $width / $height;
            $scale_src = $image->getWidth() / $image->getHeight();
            if($scale > $scale_src) {
                $width_crop = $image->getWidth();
                $height_crop = $width_crop / $scale;
            } else {
                $height_crop = $image->getHeight();
                $width_crop = $height_crop * $scale;
            }
            $image->crop((int)$width_crop, (int)$height_crop)->resize($width, $height);
        }

        $response = $response->withHeader("Content-Type", "image/jpeg")->withStatus(200)->withBody($image->stream("jpg"));
        return $response;
    }
}
