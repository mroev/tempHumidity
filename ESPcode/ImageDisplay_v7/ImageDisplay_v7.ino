#include <Arduino.h>
#include <ESP32-HUB75-MatrixPanel-I2S-DMA.h>
#include <string.h>
#include "WiFi.h"
#include <HTTPClient.h>
#include <Preferences.h>
#include <DHT.h>
 
// Define pin mappings for the LED matrix
#define R1_PIN 25 // Red 1 pin
#define G1_PIN 26 // Green 1 pin
#define B1_PIN 27 // Blue 1 pin
#define R2_PIN 14 // Red 2 pin
#define G2_PIN 12 // Green 2 pin
#define B2_PIN 13 // Blue 2 pin
#define A_PIN 23  // A pin
#define B_PIN 19  // B pin
#define C_PIN 5   // C pin
#define D_PIN 17  // D pin
#define E_PIN 18  // E pin
#define LAT_PIN 4 // Latch pin
#define OE_PIN 15 // Output enable pin
#define CLK_PIN 16 // Clock pin
 
// Define matrix panel configuration
#define PANEL_RES_X 64 // Panel width
#define PANEL_RES_Y 64 // Panel height
#define PANEL_CHAIN 1  // Number of chained panels
 
// Initialize DHT sensor on pin 22
DHT dht(22, DHT22);
Preferences preferences; // Preferences to store settings
MatrixPanel_I2S_DMA *dma_display = nullptr; // Pointer to the matrix panel display
 
// Define the pin configuration for the HUB75 matrix
HUB75_I2S_CFG::i2s_pins _pins = {R1_PIN, G1_PIN, B1_PIN, R2_PIN, G2_PIN, B2_PIN, A_PIN, B_PIN, C_PIN, D_PIN, E_PIN, LAT_PIN, OE_PIN, CLK_PIN};
HUB75_I2S_CFG mxconfig(
    64,  // width
    64,  // height
    1,   // chain length
    _pins // pin mapping
);
 
String version = "V6"; // Firmware version
 
// Network configuration variables
String ssid;
String password;
 
// Timer configuration
long lastTime = 0;
long timerDelay = 0;
 
long lastTempHumidityTime = 0;
long tempHumidityInterval = 300000; // 5 minutes
 
 
int disconnect_counter = 0;
int brightness = 255;
bool settings = false;
bool rotation_180;
String MacOverride = "0";
String serverName;
String tmpName;
String Sinput;
String current_status = "x";
 
char img[24577]; // Buffer for image data
 
// Function to get color value from a string representation
int colorFromString(char Str[], int x, int y, int channel) {
    char hex[3];
    if (rotation_180) {
        x = 63 - x;
        y = 63 - y;
    }
    int index = (((x * 64 + y) * 3) + channel) * 2;
    hex[0] = Str[index];
    hex[1] = Str[index + 1];
    hex[2] = '\0';
    return (int)strtol(hex, 0, 16);
}
 
// Function to draw image on the matrix panel
void drawImg() {
    for (int i = 0; i < 64; i++) {
        for (int j = 0; j < 64; j++) {
            dma_display->drawPixelRGB888(i + 1, j, colorFromString(img, j, i, 0), colorFromString(img, j, i, 1), colorFromString(img, j, i, 2));
        }
    }
}
 
void setup() {
    delay(2000); // Initial delay
    Serial.begin(115200); // Initialize serial communication
    dht.begin(); // Initialize DHT sensor
    delay(5000); // Additional delay
 
    // Initialize preferences
    preferences.begin("settings", false);
    MacOverride = preferences.getString("MacOverride", "0");
    ssid = preferences.getString("ssid", "beispiel");
    password = preferences.getString("password", "beispiel");
    timerDelay = preferences.getLong("refresh_time", 20000);
    brightness = preferences.getInt("brightness", 255);
    rotation_180 = preferences.getBool("rotation_180", true);
 
    // Set server name based on MAC address or override
    if (MacOverride == "0") {
        serverName = preferences.getString("api_url", "https://beispiel/return.php?mac_adress=") + WiFi.macAddress();
       
    } else {
        serverName = preferences.getString("api_url", "https://beispiel/return.php?mac_adress=") + MacOverride;
    }
 
    // Initialize the matrix panel
    dma_display = new MatrixPanel_I2S_DMA(mxconfig);
    dma_display->begin();
    dma_display->setBrightness8(brightness); // Set brightness (0-255)
    dma_display->clearScreen();
 
    // Display setup status
    if (ssid == "" || password == "") {
        dma_display->setTextSize(1); // Text size 1 (8 pixels high)
        dma_display->setTextWrap(true);
        dma_display->setCursor(3, 4);
        dma_display->print("not");
        dma_display->setCursor(6, 14);
        dma_display->print("set up");
        Serial.println("Not configured. Please enter SSID, password, and API URL.");
        Serial.println("Type 'help' or 'setup' to configure, 'exit' to exit setup.");
    } else {
        dma_display->setTextSize(1);
        dma_display->setTextWrap(true);
        dma_display->setCursor(3, 4);
        dma_display->print("Connecting");
        dma_display->setCursor(6, 14);
        dma_display->print(ssid);
 
        // Connect to WiFi
        WiFi.begin(ssid.c_str(), password.c_str());
        Serial.println("Connecting to WiFi network");
 
        int dotloop = 0;
        while (WiFi.status() != WL_CONNECTED && dotloop < 30) {
            delay(500);
            Serial.print("Connecting to WiFi...");
            Serial.println(WiFi.status());
            Serial.println(WiFi.RSSI());
            dma_display->setCursor(3, 10 + 8 * dotloop);
            dma_display->print(".");
            dotloop++;
        }
 
        // Display connection status
        dma_display->fillScreen(dma_display->color444(0, 0, 0));
        dma_display->setCursor(3, 4);
        dma_display->print("Connected");
        dma_display->setCursor(9, 20);
        dma_display->print(WiFi.localIP().toString().substring(0, 7));
        dma_display->setCursor(9, 29);
        dma_display->print(WiFi.localIP().toString().substring(7));
        delay(5000);
 
        dma_display->fillScreen(dma_display->color444(0, 0, 0));
        dma_display->setCursor(1, 4);
        dma_display->print("MAC");
        dma_display->setCursor(3, 20);
        dma_display->print(WiFi.macAddress().substring(0, 8));
        dma_display->setCursor(3, 29);
        dma_display->print(WiFi.macAddress().substring(8));
        delay(5000);
 
        dma_display->fillScreen(dma_display->color444(0, 0, 0));
    }
}
 
void loop() {
    // Check for serial input
    if (Serial.available() > 0) {
        Sinput = Serial.readString();
        Sinput.trim();
        Serial.println(Sinput);
 
        // Display help information
        if (Sinput.equalsIgnoreCase(String("help"))) {
            Serial.println("<---------------< Help >--------------->");
            Serial.println("type:");
            Serial.println("setup  : to enter setup mode");
            Serial.println("show   : to show current settings");
            Serial.println("exit   : to exit setup mode");
            Serial.println("reboot : to reboot");
            Serial.println("<-------------------------------------->");
            Sinput = "";
        }
 
        // Enter setup mode
        if (Sinput.equalsIgnoreCase(String("setup"))) {
            settings = true;
            Serial.println("<---------------< Setup >--------------->");
            Serial.println("s:[ssid]");
            Serial.println("p:[password]");
            Serial.println("a:[api_url]");
            Serial.println("d:[refresh_time]");
            Serial.println("b:[brightness]");
            Serial.println("r:[rotation 180°(0/1)]");
            Serial.println("m:[Mac-address override (0 == real MAC address)]");
            Sinput = "";
        }
 
        // Exit setup mode
        if (Sinput.equalsIgnoreCase(String("exit"))) {
            settings = false;
            Serial.println("<-----------< Exiting setup >----------->");
            Sinput = "";
        }
 
        // Show current settings
        if (Sinput.equalsIgnoreCase(String("show"))) {
            Serial.println("<-----------< Current setup >----------->");
            Serial.println("");
            Serial.print("SSID: ");
            Serial.print(preferences.getString("ssid", "N/A"));
            Serial.println("");
            Serial.println("");
            Serial.print("Password: ");
            Serial.print(preferences.getString("password", "N/A"));
            Serial.println("");
            Serial.println("");
            Serial.print("API URL: ");
            Serial.print(preferences.getString("api_url", "N/A"));
            Serial.println("");
            Serial.println("");
            Serial.print("Full URL: ");
            Serial.print(serverName);
            Serial.println("");
            Serial.println("");
            Serial.print("Brightness: ");
            Serial.print(brightness);
            Serial.println("");
            Serial.println("");
            Serial.print("Refresh delay: ");
            Serial.print(timerDelay);
            Serial.println("");
            Serial.println("");
            Serial.print("Rotation 180: ");
            Serial.print(rotation_180);
            Serial.println("");
            Serial.println("");
            Serial.print("IP address: ");
            Serial.print(WiFi.localIP());
            Serial.println("");
            Serial.println("");
            Serial.print("MAC address: ");
            Serial.print(WiFi.macAddress());
            Serial.println("");
            Serial.println("");
            Serial.print("MAC address override: ");
            Serial.print(MacOverride);
            Serial.println("");
            Serial.println("");
            Serial.print("Firmware version: ");
            Serial.print(version);
            Serial.println("");
            Serial.println("");
            Sinput = "";
            Serial.println("<--------------------------------------->");
        }
 
        // Reboot the ESP32
        if (Sinput.equalsIgnoreCase(String("reboot"))) {
            Serial.println("<-------------< Rebooting >------------->");
            delay(2000);
            ESP.restart();
        }
 
        // Handle settings in setup mode
        if (settings) {
            char setupCommand = Sinput.charAt(0);
            if (setupCommand == 109) { // 'm' command for MAC override
                Sinput.remove(0, 2);
                preferences.putString("MacOverride", Sinput);
                Serial.print("Set MacOverride to: ");
                Serial.print(Sinput);
                Serial.println("");
                Sinput = "";
            }
            if (setupCommand == 115) { // 's' command for SSID
                Sinput.remove(0, 2);
                preferences.putString("ssid", Sinput);
                Serial.print("Set SSID to: ");
                Serial.print(Sinput);
                Serial.println("");
                Sinput = "";
            }
            if (setupCommand == 112) { // 'p' command for password
                Sinput.remove(0, 2);
                preferences.putString("password", Sinput);
                Serial.print("Set password to: ");
                Serial.print(Sinput);
                Serial.println("");
                Sinput = "";
            }
            if (setupCommand == 97) { // 'a' command for API URL
                Sinput.remove(0, 2);
                preferences.putString("api_url", Sinput);
                Serial.print("Set API URL to: ");
                Serial.print(Sinput);
                Serial.println("");
                serverName = preferences.getString("api_url", "") + WiFi.macAddress();
                Sinput = "";
            }
            if (setupCommand == 100) { // 'd' command for refresh time
                Sinput.remove(0, 2);
                preferences.putLong("refresh_time", Sinput.toInt());
                Serial.print("Set refresh time to: ");
                Serial.print(Sinput);
                Serial.println("");
                timerDelay = preferences.getLong("refresh_time", 20000);
                Sinput = "";
            }
            if (setupCommand == 98) { // 'b' command for brightness
                Sinput.remove(0, 2);
                preferences.putInt("brightness", Sinput.toInt());
                Serial.print("Set brightness to: ");
                Serial.print(Sinput);
                Serial.println("");
                brightness = preferences.getInt("brightness", 255);
                Sinput = "";
            }
            if (setupCommand == 114) { // 'r' command for rotation
                Sinput.remove(0, 2);
                bool rotation = false;
                if (Sinput.toInt() == 1) {
                    rotation = true;
                }
                preferences.putBool("rotation_180", rotation);
                Serial.print("Set rotation 180 to: ");
                Serial.print(rotation);
                Serial.println("");
                rotation_180 = preferences.getBool("rotation_180", false);
                Sinput = "";
            }
        }
    }
 
    // Periodically check and update data
    if ((millis() - lastTime) > timerDelay && !settings) {
        // Check WiFi connection status
        if (WiFi.status() == WL_CONNECTED) {
            // Read temperature and humidity
            float temp = dht.readTemperature();
            float humidity = dht.readHumidity();
 
            // Prepare URL with current status
            tmpName = serverName;
            Serial.println(tmpName);
            if ((millis() - lastTempHumidityTime) > tempHumidityInterval && !settings) {
              if (!isnan(temp) && !isnan(humidity)) {
                tmpName += "&current_status=" + current_status;
                tmpName += "&temp=";
                tmpName += String(temp);
                tmpName += "&humidity=";
                tmpName += String(humidity);
                Serial.println(tmpName);
                lastTempHumidityTime = millis();
              }
              else{
                Serial.println("Error reading temperature or humidity!");
                tmpName += "&current_status=" + current_status;
              }
            } else {
                tmpName += "&current_status=" + current_status;
            }
 
            HTTPClient http;
            http.begin(tmpName);
 
            // Send HTTP GET request
            int httpResponseCode = http.GET();
 
            if (httpResponseCode > 0) {
                disconnect_counter = 0;
                Serial.print("HTTP Response code: ");
                Serial.println(httpResponseCode);
                String payload = http.getString();
                if (payload.charAt(0) == 107) { // ASCII 'k'
                    Serial.println("Keeping image");
                } else {
                    current_status = payload[0];
                    payload.remove(0, 1);
                    strcpy(img, payload.c_str());
                    delay(200);
                    Serial.println("Refreshing image:");
                    drawImg();
                }
            } else {
                Serial.print("Error code: ");
                disconnect_counter++;
                Serial.println(httpResponseCode);
            }
            http.end(); // Free resources
        } else {
            Serial.println("WiFi Disconnected");
            disconnect_counter++;
        }
        lastTime = millis();
    }
 
    // Restart ESP32 if disconnected too many times
    if (disconnect_counter > 10) {
        ESP.restart();
    }
}