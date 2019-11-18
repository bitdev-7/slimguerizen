<?php declare(strict_types=1);

namespace App\Controller\User;

use App\Controller\BaseController;
use App\Exception\UserException;
use App\Service\LogService;
use Intervention\Image\ImageManager;
use Nette\Utils\Random;
use Ramsey\Uuid\Uuid;
use Slim\Http\Request;
use Slim\Http\Response;

class ImagesUpload extends BaseUser
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->setParams($request, $response, $args);

        $token = $this->container->get('token');
        $user = $this->getUserService()->getUserByEmail($token['user_id']);

        // check input file and copy to upload folder
        $files = $this->request->getUploadedFiles();

        $fileInfo['clientName'] = $files['image']->getClientFilename();
        $fileInfo['contentType'] = $files['image']->getClientMediaType();
        $fileInfo['size'] = $files['image']->getSize();
        $fileInfo['path'] = Random::generate(20);

        LogService::dump($fileInfo, 'fileInfo');

        $pathTo = $_SERVER["DOCUMENT_ROOT"] . "/../upload/" . $fileInfo['path'];

        LogService::log($pathTo);

        $files['image']->moveTo($pathTo);

        $manager = new ImageManager();

        try {
            $image = $manager->make($pathTo);
            $fileInfo['width'] = $image->width();
            $fileInfo['height'] = $image->height();
        } catch(ImageException $e) {
            throw new UserException($e->getMessage(), 400);
        }

        $url = $this->baseUrl . "/image?f=" . $fileInfo['path'];

        $isProfile = false;
        $referers = $this->request->getHeader('Referer');
        foreach($referers as $referer) {
            if(strpos($referer, 'profile-settings') !== false) {
                $isProfile = true;
                break;
            }
        }

        if($isProfile) {
            $this->getUserService()->updateImage($user['uuid'], $fileInfo);
            $uuid = Uuid::uuid4()->toString();
        } else {
            $uuid = $this->getUserService()->addListingImage($fileInfo);
        }

        $resp = ["^ ",
            "~:data", ["^ ",
                "~:id","~u" . $uuid,
                "~:type","~:image",
                "~:attributes",["^ ",
                    "~:variants",["^ ",
                        "~:square-small2x",["^ ",
                            "~:height",480,
                            "~:width",480,
                            "~:url","$url&w=480&h=480",
                            "~:name","^6"],
                        "~:square-small",["^ ",
                            "^7",240,
                            "^8",240,
                            "^9","$url&w=240&h=240",
                            "^:","^;"]
                    ]
                ]
            ]
        ];

        return $this->response->withJson($resp, 200, JSON_PRETTY_PRINT);
    }
}
