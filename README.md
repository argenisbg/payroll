<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"></a></p>

## Laravel 8 Project
This project is an example of a payroll timesheet calculation

### Technical Requirements
PHP >= 7.3<br>
Apache or NginX<br>
Composer<br>

### Steps to reproduce the API Call
:one: Clone repository <br>
:two: Duplicate the .env.example file and rename it just to .env
:three: Start the artisan server: php artisan serve <br>
:four: You can import this postman collection with the request: <a href="https://drive.google.com/file/d/1wuqM5O2MzmU_8bYWAEW965F0jPfMnxoH/view?usp=sharing">Postman File</a><br>
        Or use CURL in your terminal:<br>
        curl --location --request POST 'http://127.0.0.1:8000/api/payroll/calculate' \<br>
--form 'file=@/absolute/path/to/your/file/TimeSheetData.json'<br>
:five: You will see the JSON response with the expected information

### Architecture
My thinking is that the API could be installed in a EC2 instance with a LEMP stack
Configure the file storage with a bucket in AWS S3
Configure the domain or subdomain with Route 53 to get a special endpoint

### Security checkpoints
Amazon allows to configure the inbound and outbound rules to set one layer of security about the origin of requests ip

### Notes
There is no need to a database, because its all about to retrieve the calculation and response with the JSON<br> But to keep information saved, I leave every json file stored in the project

