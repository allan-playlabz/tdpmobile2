<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Aws\Resource\Aws;
use Aws\S3\S3Client;
use Cake\Filesystem\File;
use Mimey\MimeTypes;
use Aws\S3\Exception\S3Exception;

class S3PublicComponent extends Component
{ 
  private $_main_folder = '';

  public function __construct($component_registry = null,$options = []) {
    parent::__construct($component_registry,$options);
    $this->_main_folder = Configure::read('AWS.prefix');
  }

  public function headObject($key,$contentOptions = []){
    $s3 = S3Client::factory(Configure::read('AWSPublic'));
    try {
      $options = [
        'Bucket' => Configure::read('AWSPublic.Bucket'),
        'Key'    => $this->_main_folder.$key,        
      ];
      if (!empty($contentOptions)) {
        $options = array_merge($options,$contentOptions);
      }
      $result = $s3->headObject($options);
      return $result;
    } catch (S3Exception $e) {
      return false;
    }
    catch (\Exception $e) {
      return false;
    }
  }

  public function getObject($key,$contentOptions = []){
    $s3 = S3Client::factory(Configure::read('AWSPublic'));
    try {
      $options = [
        'Bucket' => Configure::read('AWSPublic.Bucket'),
        'Key'    => $this->_main_folder.$key,        
      ];
      if (!empty($contentOptions)) {
        $options = array_merge($options,$contentOptions);
      }      
      $result = $s3->getObject($options);
      return $result['Body'];
    } catch (S3Exception $e) {
      return false;
    }
    catch (\Exception $e) {
      return false;
    }
  }

  public function putObject($key,$file,$main_folder = null){
    $s3 = S3Client::factory(Configure::read('AWSPublic'));
    $mime = $this->getMimeType($file,$this->_main_folder.$key);
    try {
      
      $result = $s3->putObject(array(
        'Bucket'        => Configure::read('AWSPublic.Bucket'),
        'Key'          => $this->_main_folder.$key,
        'SourceFile'   => $file,
        'ContentType'  => $mime,
        'ACL' => 'public-read',
        'StorageClass' => 'REDUCED_REDUNDANCY', 
      ));            

      if($result){   
        return $this->_main_folder.$key;
      }
    } 
    catch (S3Exception $e) {
      \Cake\Log\Log::write('debug',$e->getMessage());
      return false;
    }
    catch (\Exception $e) {
      \Cake\Log\Log::write('debug',$e->getMessage());
      return false;
    }  
  
  }

  public function putObjectAsStream($key, $string, $contentType=null){
    $s3 = S3Client::factory(Configure::read('AWSPublic'));
    if ($contentType==null) {
      $contentType = $this->getMimeType($this->_main_folder.$key);
    }
    try {
      
      $result = $s3->putObject(array(
        'Bucket'        => Configure::read('AWSPublic.Bucket'),
        'Key'          => $this->_main_folder.$key,
        'Body'   => $string,
        'ContentType'  => $contentType,
        'ACL' => 'public-read',
        'StorageClass' => 'REDUCED_REDUNDANCY',
      ));
      if($result){   
        return $this->_main_folder.$key;
      }
    } 
    catch (S3Exception $e) {
      \Cake\Log\Log::debug($e);
      return false;
    }
    catch (\Exception $e) {
      \Cake\Log\Log::debug($e);
      return false;
    }
  
  }

  public function ListObjects($bucket,$prefix, $delimiter=null){
    $s3 = S3Client::factory(Configure::read('AWSPublic'));
    try {
      return $s3->ListObjects(["Bucket" => $bucket,
        "Prefix" => $prefix,
        "Delimiter" => $delimiter]);
    } catch (S3Exception $e) {
      return false;
    }
    catch (\Exception $e) {
      return false;
    }
    
  }

  public function getIterator($bucket,$prefix,$delimiter=null){
    $s3 = S3Client::factory(Configure::read('AWSPublic'));
    try {
      return $s3->getIterator('ListObjects',["Bucket" => $bucket,
        "Prefix" => $prefix,
        "Delimiter" => $delimiter]);
    } catch (S3Exception $e) {
      return false;
    }
    catch (\Exception $e) {
      return false;
    }
    
  }

  public function copyObject($args){
    $s3 = S3Client::factory(Configure::read('AWSPublic'));
    return $s3->copyObject($args);
  }

  public function getCommand($command,$args){
    $s3 = S3Client::factory(Configure::read('AWSPublic'));
    return $s3->getCommand($command,$args);
  }

  public function execute($batch){
    $s3 = S3Client::factory(Configure::read('AWSPublic'));
    try {
      return $s3->execute($batch);
    } catch (S3Exception $e) {
      return false;
    }
    catch (\Exception $e) {
      return false;
    }
    
  }

  public function deleteObject($key){
    $s3 = S3Client::factory(Configure::read('AWSPublic')); 
    try {
      $result = $s3->deleteObject([
        'Bucket' => Configure::read('AWSPublic.Bucket'),
        'Key'    => $key,        
      ]);
      
      return true;
    } catch (S3Exception $e) {
      return false;
    }
    catch (\Exception $e) {
      return false;
    }
  }

  public function deleteObjects($keys){
    $s3 = S3Client::factory(Configure::read('AWSPublic')); 
    try {
      $result = $s3->deleteObjects([
        'Bucket' => Configure::read('AWSPublic.Bucket'),
        'Delete'    => [
          'Objects' => $keys
        ]        
      ]);
      
      return true;
    } catch (S3Exception $e) {
      return false;
    }
    catch (\Exception $e) {
      return false;
    }
  }

  public function getFolderSize($prefix){
    $s3 = S3Client::factory(Configure::read('AWSPublic')); 
    $size = 0;
    $objects = $s3->getIterator('ListObjects', array(
        "Bucket" => Configure::read('AWS.Bucket'),
        "Prefix" => $prefix
    ));
    foreach ($objects as $object) {
        $size = $size + $object['Size'];
    }
    return $size;
  }

  public function getMimeType($file,$key=null) {
    $mime = (new File($file))->mime();
    if (!$mime) {
      $mimeType = new MimeTypes;
      $mime = $mimeType->getMimeType(
        array_values(array_slice(explode('.',$key), -1))[0]
          );
    }
    if(!$mime) {
      $mime = "text/plain";
    }
    return $mime;
  }
}