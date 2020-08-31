# Luniverse Shielding
This is a free tool for detecting and preventing brute force attacks to secure your applications, reduce server load and logfile sizes.

#### Workflow
1. A Windows service monitors incoming requests using special **adapters**
1. The IP addresses of malicious requests are collected and stored in a database
1. Upon a scoring mechanism, IP addresses are added to a firewall rule that blocks connections

#### Requirements
- Windows Server with administrator access
- PHP with the [`win32service`](https://pecl.php.net/package/win32service) extension
- MySQL database

## Getting started
Have a look at the example in [`service.sample.php`](service.sample.php).
Fill out all `<data>` and choose one or more adapters in the constructor of `Shielding`. Currently included are:
- [`winevent.php`](adapter/winevent.php) – extracts failed login attempts via RDP, SMB, FTP, etc. from the Windows Event Log
- [`mailenable.php`](adapter/mailenable.php) – extracts failed login attempts via SMTP from MailEnable activity logs

Install the service, firewall rule and MySQL table by opening a command prompt and typing:
```
php service.sample.php install
```
The newly created service should then appear in the Windows Service Control Manager (SCM) and can be started from there.
You can check if everything works well by inspecting the logfiles, MySQL table and firewall rule.

#### Configuration
- Decrease or increase `$shielding->threshold` for a more strict or moderate blocking.
- The parameter `interval` determines how long PHP waits before checking the service status again (in microseconds).
- The parameter `pause` is the time in seconds between single adapter calls.

#### Additional information
- You can also create your own adapters by implementing the `getList()`-method. Please be sure to add a pull request!
- Only one request per second and IP will be stored. Change this by removing the MySQL `UNIQUE KEY`.
- Uninstall service and firewall using `php service.sample.php uninstall`. This will **not** delete the MySQL table.
- `chdir(__DIR__)` is neccessary for a relative logpath because the service is started from within `C:\Windows\System32`.
- The "badness" of an IP address is calculated by the total amount of requests and how recent they were.
- This software is licensend under the [MIT License](LICENSE). Feel free to contribute or report issues!
