<?php

class PicturesController extends GalleryAppController
{
    public $components = array('Gallery.Util');
    public $uses = array('Gallery.Album', 'Gallery.Picture');

    public function upload()
    {
        $album_id = $_POST['album_id'];

        # Resize attributes configured in bootstrap.php
        $resize_attrs = $this->Picture->getResizeToSize();

        if ($_FILES) {
            $file = $_FILES['file'];

            try {
                # Check if the file have any errors
                $this->Util->checkFileErrors($file);

                # Get file extention
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);

                # Validate if the file extention is allowed
                $this->Util->validateExtensions($ext);

                # Generate a random filename
                $filename = $this->Util->getToken();

                $path = WWW_ROOT . 'files' . DS . 'gallery' . DS . $album_id . DS . $filename . '.' . $ext;

                $main_id = $this->Picture->uploadFile(
                    $path,
                    $album_id,
                    $file['name'],
                    $file['size'],
                    $file['tmp_name'],
                    $resize_attrs['width'],
                    $resize_attrs['height'],
                    $resize_attrs['action'],
                    true
                );


                # Create extra pictures from the original one
                $this->Picture->createExtraImages(
                    Configure::read('GalleryOptions.Pictures.styles'),
                    $file['name'],
                    $file['size'],
                    $file['tmp_name'],
                    $album_id,
                    $main_id,
                    $filename
                );

            } catch (ForbiddenException $e) {
                $response = $e->getMessage();
                return new CakeResponse(
                    array(
                        'status' => 401,
                        'body' => json_encode($response)
                    )
                );
            }
        }

        $this->render(false, false);
    }

    /**
     * Delete an image and all its versions from database
     * @param $id
     */
    public function delete($id)
    {
        # Delete the picture and all its versions
        $this->Picture->deletePictures($id);

        $this->render(false, false);
    }

    /**
     * Sort pictures from an album
     */
    public function sort()
    {
        if ($this->request->is('post')) {
            $order = explode(",", $_POST['order']);
            $i = 1;
            foreach ($order as $photo) {
                $this->Picture->read(null, $photo);
                $this->Picture->set('order', $i);
                $this->Picture->save();
                $i++;
            }
        }

        $this->render(false, false);
    }


}

?>
