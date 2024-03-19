#include <ArduinoLowPower.h>
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
  Serial.println("Ready.");

  if (!SigFox.begin()) {
    Serial.println("Unable to init the Atmel 1DB4E6 Sigfox chipset");
    return;
  }
  SigFox.debug();

  dht.begin();

  pinMode(1, INPUT);

  LowPower.attachInterruptWakeup(1, SigFox_Data_Sender, PinStatus::RISING);

  /*
  TO DO...
  */
}

void loop() {
  SigFox_Data_Sender();
  GoToSleep();
}

void GoToSleep() {
  Serial.println("MKR FOX 1200 - Going in sleep");
  Serial.flush();
  Serial.end();
  LowPower.deepSleep(HOUR);
}

void sendPacket(float temp, float hum) {
  Serial.println("Begin data transmission");
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

void SigFox_Data_Sender() {
  Serial.begin(9600);
  dht.begin();
  delay(500);
  Serial.println("Begin data reading");
  float temp, hum;

  // Read temperature and humidity
  hum = dht.readHumidity();
  temp = dht.readTemperature(false);

  if (isnan(hum) || isnan(temp)) {
    Serial.println(F("Failed to read from DHT sensor!"));
    return;
  }

  Serial.println("Data read successfully");
  sendPacket(temp, hum);
}
