Alarm Server
============

A small web application to receive and store images from alarm systems.

The goal is simple: allow clients to store files but do not allow them to alter files. So in case intruders get access
to one of the alarm systems, they can not remove their traces.
Additionally the application notifies administrators by e-mail that a file was received. It also keeps audit logs.

Later on the application will be extended to allow for easy browsing of the captured images.

## Installation

    composer install
    php bin/console doctrine:database:create 
    php bin/console doctrine:schema:update --force 

    php bin/console fos:user:create alarm-user
    php bin/console fos:user:promote alarm-user ROLE_ALARM

## Dev Server

    php -S 127.0.0.1:8000 -t public

## Usage
This section explains how clients can use the functionality that the server provides. Since it is a web server all
actions can be done with `curl`.

### Alarm
To trigger the alarm simply send a file with the name `alarm[file]` to `/upload`.

#### Example

    curl -F "alarm[file]=@image.jpg" -u alarm-user:password http://127.0.0.1:8000/upload

### Ping
To let the alarm server know that you are still alive send a request to `/ping`. Run `./bin/console as:alive:notify`
on the server to notify administrators by e-mail about dead clients.

#### Example

    curl -u alarm-user:password http://127.0.0.1:8000/ping