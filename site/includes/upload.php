<?php

class  Media {

  public $imageInfo;
  public $fileName;
  public $fileType;
  public $fileTempPath;
  //Set destination for upload
  public $userPath = SITE_ROOT.DS.'..'.DS.'uploads/users';
  public $productPath = SITE_ROOT.DS.'..'.DS.'uploads/products';


  public $errors = array();
  public $upload_errors = array(
    0 => 'Não há erro, o arquivo foi enviado com sucesso',
    1 => 'O arquivo enviado excede a diretiva upload_max_filesize no php.ini',
    2 => 'O arquivo carregado excede a diretiva MAX_FILE_SIZE que foi especificada no formulário HTML',
    3 => 'O arquivo enviado foi enviado apenas parcialmente',
    4 => 'Nenhum arquivo foi enviado',
    6 => 'Faltando uma pasta temporária',
    7 => 'Falha ao gravar o arquivo no disco.',
    8 => 'Uma extensão PHP interrompeu o upload do arquivo.'
  );
  public$upload_extensions = array(
   'gif',
   'jpg',
   'jpeg',
   'png',
   'webp'
  );
  public function file_ext($filename){
     $ext = strtolower(substr( $filename, strrpos( $filename, '.' ) + 1 ) );
     if(in_array($ext, $this->upload_extensions)){
       return true;
     }
   }

  public function upload($file)
  {
    if(!$file || empty($file) || !is_array($file)):
      $this->errors[] = "Nenhum arquivo foi enviado.";
      return false;
    elseif($file['error'] != 0):
      $this->errors[] = $this->upload_errors[$file['error']];
      return false;
    elseif(!$this->file_ext($file['name'])):
      $this->errors[] = 'Formato de arquivo incorreto ';
      return false;
    else:
      $this->imageInfo = getimagesize($file['tmp_name']);
      $this->fileName  = basename($file['name']);
      $this->fileType  = $this->imageInfo['mime'];
      $this->fileTempPath = $file['tmp_name'];
     return true;
    endif;

  }

 public function process(){

    if(!empty($this->errors)):
      return false;
    elseif(empty($this->fileName) || empty($this->fileTempPath)):
      $this->errors[] = "O local do arquivo não está disponível.";
      return false;
    elseif(!is_writable($this->productPath)):
      $this->errors[] = $this->productPath." Deve ter permissões de gravação!!!.";
      return false;
    elseif(file_exists($this->productPath."/".$this->fileName)):
      $this->errors[] = "O arquivo {$this->fileName} realmente existe.";
      return false;
    else:
     return true;
    endif;
 }
 /*--------------------------------------------------------------*/
 /* Function for Process media file
 /*--------------------------------------------------------------*/
  public function process_media(){
    if(!empty($this->errors)){
        return false;
      }
    if(empty($this->fileName) || empty($this->fileTempPath)){
        $this->errors[] = "O local do arquivo não está disponível.";
        return false;
      }

    if(!is_writable($this->productPath)){
        $this->errors[] = $this->productPath." Deve ter permissões de gravação!!!.";
        return false;
      }

    if(file_exists($this->productPath."/".$this->fileName)){
      $this->errors[] = "O arquivo {$this->fileName} Realmente existe.";
      return false;
    }

    if(move_uploaded_file($this->fileTempPath,$this->productPath.'/'.$this->fileName))
    {

      if($this->insert_media()){
        unset($this->fileTempPath);
        return true;
      }

    } else {

      $this->errors[] = "Falha no upload do arquivo, possivelmente devido a permissões incorretas da pasta de upload.";
      return false;
    }

  }
  /*--------------------------------------------------------------*/
  /* Function for Process user image
  /*--------------------------------------------------------------*/
 public function process_user($id){

    if(!empty($this->errors)){
        return false;
      }
    if(empty($this->fileName) || empty($this->fileTempPath)){
        $this->errors[] = "O local do arquivo não estava disponível.";
        return false;
      }
    if(!is_writable($this->userPath)){
        $this->errors[] = $this->userPath." Deve ter permissões de gravação";
        return false;
      }
    if(!$id){
      $this->errors[] = " ID de usuario ausente.";
      return false;
    }
    $ext = explode(".",$this->fileName);
    $new_name = randString(8).$id.'.' . end($ext);
    $this->fileName = $new_name;
    if($this->user_image_destroy($id))
    {
    if(move_uploaded_file($this->fileTempPath,$this->userPath.'/'.$this->fileName))
       {

         if($this->update_userImg($id)){
           unset($this->fileTempPath);
           return true;
         }

       } else {
         $this->errors[] = "Falha no upload do arquivo, possivelmente devido a permissões incorretas da pasta de upload.";
         return false;
       }
    }
 }
 /*--------------------------------------------------------------*/
 /* Function for Update user image
 /*--------------------------------------------------------------*/
  private function update_userImg($id){
     global $db;
      $sql = "UPDATE users SET";
      $sql .=" image='{$db->escape($this->fileName)}'";
      $sql .=" WHERE id='{$db->escape($id)}'";
      $result = $db->query($sql);
      return ($result && $db->affected_rows() === 1 ? true : false);

   }
 /*--------------------------------------------------------------*/
 /* Function for Delete old image
 /*--------------------------------------------------------------*/
  public function user_image_destroy($id){
     $image = find_by_id('users',$id);
     if($image['image'] === 'no_image.jpg')
     {
       return true;
     } else {
       unlink($this->userPath.'/'.$image['image']);
       return true;
     }

   }
/*--------------------------------------------------------------*/
/* Function for insert media image
/*--------------------------------------------------------------*/
  private function insert_media(){

         global $db;
         $sql  = "INSERT INTO media ( file_name,file_type )";
         $sql .=" VALUES ";
         $sql .="(
                  '{$db->escape($this->fileName)}',
                  '{$db->escape($this->fileType)}'
                  )";
       return ($db->query($sql) ? true : false);

  }
/*--------------------------------------------------------------*/
/* Function for Delete media by id
/*--------------------------------------------------------------*/
   public function media_destroy($id,$file_name){
     $this->fileName = $file_name;
     if(empty($this->fileName)){
         $this->errors[] = "Arquivo de foto ausente.";
         return false;
       }
     if(!$id){
       $this->errors[] = "ID de foto ausente.";
       return false;
     }
     if(delete_by_id('media',$id)){
         unlink($this->productPath.'/'.$this->fileName);
         return true;
     } else {
       $this->error[] = "Falha na exclusão da foto.";
       return false;
     }

   }



}


?>
