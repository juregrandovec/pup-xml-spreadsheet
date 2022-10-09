# PUP-XML-spreadsheet

This is Symfony Console XML file parser that uploads data to google spreadsheet

## Prerequisites
This project uses requires:
* [Docker](https://www.docker.com/products/docker-desktop/)
* [Google Cloud - Service Account](https://console.cloud.google.com/) with enabled [Google Sheets Api](https://developers.google.com/sheets/api) and the `.json` file credentials
* XML file or FTP credentials 
## Setup
1. Environment variable and credentials:
   1. Download the [Google Sheets Api](https://developers.google.com/sheets/api) `.json` file credentials and save it into the project folder (prefferably into /application/storage/tmp)
   2. Create a copy of file `.env.example` name it `.env` and enter the FTP credentials
   3. Enter the path to credentials `.json` file relative to application folder. (e.g. `storage/tmp/credentials.json`)
2. Running containers:
   1. open project root in terminal and run `docker-compose up -d` command to build and start the docker container
3. Installing dependencies:
   1. connect into the docker container with `docker exec -it pup-xml-app bash`. This command will open a bash console that enables the use of the application and composer
   2. install composer packages `composer i`

## Usage
#### This application has to be used within the docker container, so please use the `docker exec` command.

Use `php bin/console xml -h` to display all the options and arguments of the application
* Parse Local XML file:
  * `php bin/console xml local` for usage with default options
  
* Parse XML file from FTP server:
  * `php bin/console xml ftp` for usage with default options


## Tests
This application uses PHPUnit testing tool. Tests are located in src/Tests.

Use `./vendor/bin/phpunit src/Tests` from `/var/www` folder in the container to run the tests
