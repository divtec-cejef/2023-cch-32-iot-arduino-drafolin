#include <DHT.h>
#include <DHT_U.h>
#include <SigFox.h>

const int SECOND = 1000;
const int MINUTE = 60 * SECOND;
const int HOUR = 60 * MINUTE;

DHT dht(5, DHT11);
bool hasSent = false;
int totalTime = 0;

void setup() {
  Serial.begin(9600);
  while (!Serial) {
  }
  Serial.println("Ready.");

  if (!SigFox.begin()) {
    Serial.println("Unable to init the Atmel 1DB4E6 Sigfox chipset");
    return;
  }
  SigFox.debug();

  pinMode(1, INPUT);

  dht.begin();

  /*
  TO DO...
  */
}

void loop() {
  delay(200);
  totalTime += 200;
  if (digitalRead(1) == 0) {
    hasSent = false;
  } else if (!hasSent) {
    manageData();
    hasSent = true;
    totalTime = 0;
    return;
  }

  if (totalTime > HOUR) {
    manageData();
    totalTime -= HOUR;
    return;
  }
}

void sendPacket(float temp, float hum) {
  SigFox.begin();
  SigFox.beginPacket();
  SigFox.write(temp);
  SigFox.write(hum);
  int status = SigFox.endPacket();
  SigFox.end();
  if (status == 0) {
    Serial.println("Packet sent successfully");
  } else {
    Serial.println("Error while sending packet");
  }
}

void manageData() {
  float temp, hum;

  // Read temperature and humidity
  hum = dht.readHumidity();
  temp = dht.readTemperature(false);

  if (isnan(hum) || isnan(temp)) {
    Serial.println(F("Failed to read from DHT sensor!"));
    return;
  }
  sendPacket(temp, hum);
}
