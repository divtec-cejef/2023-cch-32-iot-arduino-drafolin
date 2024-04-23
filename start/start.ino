#include <ArduinoLowPower.h>
#include <DHT.h>
#include <DHT_U.h>
#include <SigFox.h>
#include <iomanip>
#include <sstream>

const int SECOND = 1;
const int MINUTE = 60 * SECOND;
const int HOUR = 60 * MINUTE;

DHT dht(5, DHT11);
bool hasSent = false;
int totalTime = 0;

unsigned int loopDelaySeconds = HOUR;

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
}

void loop() {
  SigFox_Data_Sender();
  GoToSleep();
}

void GoToSleep() {
  Serial.println("MKR FOX 1200 - Going in sleep");
  Serial.flush();
  Serial.end();
  LowPower.deepSleep((int)(loopDelaySeconds * 1000));
}

void sendPacket(float temp, float hum) {
  Serial.println("Begin data transmission");

  // Start the module
  SigFox.begin();
  // Wait at least 30mS after first configuration (100mS before)
  delay(100);
  // Clears all pending interrupts
  SigFox.status();
  delay(1);

  SigFox.beginPacket();
  SigFox.write(temp);
  SigFox.write(hum);
  int status = SigFox.endPacket(true);

  if (status > 0) {
    Serial.println("No transmission");
  } else {
    Serial.println("Transmission ok");
  }

  Serial.println(SigFox.status(SIGFOX));
  Serial.println(SigFox.status(ATMEL));

  if (SigFox.parsePacket()) {
    std::stringstream message;
    while (SigFox.available()) {
      message << std::setfill('0') << std::setw(2) << std::hex << SigFox.read();
    }

    message >> loopDelaySeconds;

  } else {
    Serial.println("Could not get any response from the server");
    Serial.println("Check the Sigfox coverage in your area");
    Serial.println(
        "If you are indoor, check the 20 dB coverage or move near a window");
  }

  SigFox.end();
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
