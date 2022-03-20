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
#### 




 
