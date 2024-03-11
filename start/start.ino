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
  }

  Serial.print("btn: ");
  Serial.println(digitalRead(1));
  Serial.print("temp: ");
  Serial.println(temp);
  Serial.print("hum: ");
  Serial.println(hum);
}
