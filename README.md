# TinyWebDB: Edge Layer for UPOD #

**Author:** Tao Zhou  
**Contributors:** Tao Zhou  
**Tags:** edge computing, data filtering, UPOD, IoT  
**Stable tag:** 1.0  
**Requires PHP:** 5.6  
**License:** GPL3.0  
**License URI:** [https://www.gnu.org/licenses/gpl-3.0.html](https://www.gnu.org/licenses/gpl-3.0.html)

## Description ##

**TinyWebDB** serves as an **Edge Layer** to process and filter IoT and dynamic data before forwarding only meaningful and refined information to **UPOD**, an efficient data management platform built on WordPress. This approach significantly reduces UPOD's computational burden while maintaining effective data sharing and management.

Designed with simplicity and extensibility in mind, TinyWebDB provides:
- A lightweight, text-file-based database for storing and retrieving key-value pairs.
- Data filtering and processing at the edge level to identify and forward relevant data.
- Seamless integration with UPOD through WordPress REST API, ensuring efficient data transfer.
- Compatibility with **MIT App Inventor**, allowing IoT devices or apps to interact directly with the edge layer.

## Features ##

1. **Edge Data Filtering**:
   - Filters raw data and determines which information is significant for UPOD, ensuring efficient use of storage and computing resources.
   
2. **Integration with UPOD**:
   - Automatically synchronizes refined data with UPOD via WordPress REST API.
   - Manual synchronization option for instant control.

3. **Key-Value Storage**:
   - Store and retrieve IoT or application data using key-value pairs, compatible with App Inventor's TinyWebDB component.

4. **Lightweight and Simple**:
   - Uses text files for storage, making it easy to deploy and manage on small-scale or edge devices.

5. **Dynamic Web Interface**:
   - A user-friendly interface to view, filter, and manage data locally.

## Architecture ##

**TinyWebDB** functions as an edge computing layer in the following workflow:

1. **IoT Devices**: Send raw data to TinyWebDB for storage or immediate processing.
2. **Edge Processing**: TinyWebDB filters and processes the data to determine its relevance.
3. **UPOD Integration**: Meaningful data is forwarded to UPOD (WordPress) via the REST API for long-term storage and further analysis.

## Installation ##

### Edge Layer Setup ###

1. **Download the Project**:
   - Clone or download the repository from [GitHub](https://github.com/tomtaozhou/tinywebdb).

2. **Upload Files to Server**:
   - Extract the downloaded files and upload them to a directory on your web server (e.g., `/var/www/html/tinywebdb/`).

3. **Configure `.htaccess`**:
   - Ensure the `.htaccess` file is correctly configured for routing API endpoints:
     ```apache
     RewriteEngine On
     RewriteRule ^getvalue$ index.php
     RewriteRule ^storeavalue$ index.php
     ```

4. **Set File Permissions**:
   - Ensure the directory is writable by the PHP process for storing key-value pairs and logging.

5. **Test the Service**:
   - Open `main.html` in your browser to test storing and retrieving data.

### UPOD Integration ###

1. **Configure WordPress API**:
   - Edit the `tags.php` file and update the following constants with your WordPress API details:
     ```php
     define('WP_API_URL', 'https://example.com/wp-json/wp/v2/posts');
     define('WP_USERNAME', 'example_user');
     define('WP_PASSWORD', 'example_password');
     ```

2. **Enable Automatic Synchronization**:
   - TinyWebDB will automatically filter and forward significant data to UPOD.

3. **Manual Synchronization**:
   - Use the "Sync to UPOD" button in the `tags.php` interface to upload all current data to UPOD.

## Usage ##

### As a Standalone Service ###

1. **Key-Value Storage**:
   - Use the `/storeavalue` endpoint to store key-value pairs.
   - Use the `/getvalue` endpoint to retrieve stored values.

2. **Testing Interface**:
   - Open `main.html` in your browser to test storing and retrieving data manually.

### As an Edge Layer ###

1. **Data Filtering**:
   - TinyWebDB filters incoming data based on predefined criteria in `tags.php` or other processing logic you define.
   - Example: Only data that matches specific conditions (e.g., temperature above a threshold) is forwarded to UPOD.

2. **UPOD Synchronization**:
   - Automatic: Data changes are detected, filtered, and sent to UPOD in real-time.
   - Manual: Click the "Sync to UPOD" button in `tags.php` to upload all current data.

### Example Integration with App Inventor ###

1. Use the TinyWebDB component in App Inventor.
2. Set the `ServiceURL` of the component to your TinyWebDB server URL (e.g., `http://example.com/tinywebdb/`).
3. Use `StoreValue` and `GetValue` blocks to interact with the service.

## Future Enhancements ##

- Implement advanced filtering logic using machine learning models.
- Add support for alternative database backends like SQLite for enhanced scalability.
- Expand API endpoints to support batch operations.

## Contributing ##

Contributions are welcome! Submit pull requests via the [GitHub repository](https://github.com/tomtaozhou/tinywebdb).

## License ##

This project is licensed under the [GPL 3.0](https://www.gnu.org/licenses/gpl-3.0.html).
