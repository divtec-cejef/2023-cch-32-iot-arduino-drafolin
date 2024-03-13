#include <DHT.h>
#include <DHT_U.h>
#include <SigFox.h>

DHT dht(5, DHT11);

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
  delay(2000);
  float temp, hum;

  // Read temperature and humidity
  hum = dht.readHumidity();
  temp = dht.readTemperature(false);

  if (isnan(hum) || isnan(temp)) {
    Serial.println(F("Failed to read from DHT sensor!"));
    return;
  }

  Serial.print("temp: ");
  Serial.println(temp);
  Serial.print("hum: ");
  Serial.println(hum);

  if (digitalRead(1) == 1) {
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
    return;
  }
}
