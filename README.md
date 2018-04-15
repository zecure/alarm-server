Alarm Server
============

A small web application to receive and store images from alarm systems.

The goal is simple: allow clients to store files remotely (for example images from a camera) but do not allow them to alter the files afterwards.
So in case intruders get access to one of the alarm systems, they can not remove their traces.
Additionally the application notifies administrators by e-mail that a file was received. It also keeps audit logs.

Later on the application will be extended to allow for easy browsing of the captured images or other files.

## Installation

    composer install
    ./bin/console doctrine:database:create 
    ./bin/console doctrine:schema:update --force 

At least two users are required for the most basic setup. One administrator user that will receive the notifications and one user for each alarm system.

    ./bin/console fos:user:create admin-user
    ./bin/console fos:user:promote admin-user ROLE_ADMIN
    ./bin/console fos:user:create alarm-user
    ./bin/console fos:user:promote alarm-user ROLE_ALARM

For **development** you can use the following command to start a web server.
```
php -S 127.0.0.1:8000 -t public
```

Do not use the built-in web server in **production**, use a real web server instead. The development server is slow and only for tests.

You can find more information about the web server configuration in the [Symfony documentation](https://symfony.com/doc/4.0/setup/web_server_configuration.html).

## Usage
This section explains how clients can use the functionality that the server provides. Since it is a web server all
actions can be done with `curl`.

### Alarm
To trigger the alarm simply send a file with the name `alarm[file]` to `/upload`.

#### Example
You can use the following `curl` command to upload a picture.
```
curl -F "alarm[file]=@image.jpg" -u alarm-user:password http://127.0.0.1:8000/upload
```

Combine this with `motion`, a software motion detector, and the main part of the alarm system is already done.
Your `motion.conf` should look similar to this.
```
daemon on
process_id_file /home/user/motion.pid
videodevice /dev/video0

width 800
height 600
framerate 5
minimum_frame_time 1
lightswitch 10
threshold 1500

output_pictures on
target_dir /home/user/alarm
on_picture_save curl -F "alarm[file]=@%f" -u alarm-user:password https://example.org/upload
```

### Ping
To let the alarm server know that you are still alive send a request to `/ping`. Run `./bin/console as:alive:notify`
on the server to notify administrators by e-mail about dead clients.

#### Example
You can use the following `curl` command to send a ping request.
```
curl -u alarm-user:password http://127.0.0.1:8000/ping
```