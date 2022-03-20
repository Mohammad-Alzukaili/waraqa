# mawdoo3

### **HOW TO INSTALL** :
#### 1- Run this command ,then install the package
      composer require  mawdoo3-zukaili/waraqa

#### 2- Add WaraqaIntegrationServiceProvider to bootstrap/app.php file
      $app->register(WaraqaIntegrationServiceProvider::class);
#### 3-  run the following command , then  choose WaraqaIntegrationServiceProvider from options appeared
      php artisan vendor:publish 

### **DESCRIPTION** :
#### This package is developed for mawdoo3 mediawiki websites , that need to be integrated with rabbitMQ queue messaging systems, 
#### to get articles from waraqa.   
### **FEATURES** : 
#### Command :
      php artisan waraqa:execute
#### command used to start listening to rabbitMQ connection port, and handling messages that coming from rabbitMQ.
### **Config Files**:
#### **waragaIntegration.php** File :
#### this config file will generated after vendor:publish command , inside config folder : 
    config/waragaIntegration.php
#### contain all  **Environment Variables**  needed for waraqa integration :  
####    
    'WARAQA_URL' => env('WARAQA_URL'),
    'CLIENT_ACCESS_ID' => env('CLIENT_ACCESS_ID'),
    'CLIENT_ID'=> env('CLIENT_ID'),
    'CLIENT_PASSWORD' => env('CLIENT_PASSWORD'),
    'WARAQA_USER_ID' => env('WARAQA_USER_ID'),
    'AWS_ACCESS_ID' => env('AWS_ACCESS_ID'),
    'AWS_ACCESS_KEY' => env('AWS_ACCESS_KEY'),
    'S3_BUCKET' => env('S3_BUCKET'),
    'MEDIAWIKI_PARSER_API' => env('MEDIAWIKI_PARSER_API'),
    'AQMP_CONNECTION' => env('AQMP_CONNECTION'),
    'IMAGE_SIZES' => env('IMAGE_SIZES'),
    'FULL_SERVER_URL' => env('FULL_SERVER_URL')
#### so it needed to be filled inside env or config file.





 
