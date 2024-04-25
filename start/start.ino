#include <ArduinoLowPower.h>
#include <DHT.h>
#include <DHT_U.h>
#include <SigFox.h>
#include <iomanip>
#include <sstream>

const int SECOND = 1;
const int MINUTE = 60 * SECOND;
const int HOUR = 60 * MINUTE;

// Configuration du senseur DHT
DHT dht(5, DHT11);

// Période configurée pour la boucle de l'envoi des données
unsigned int loopDelaySeconds = HOUR;

void setup() {
  // Initialise et acquite la connexion Série
  Serial.begin(9600);
  Serial.println("Ready.");

  // Initialise et acquite la connexion SigFox
  if (!SigFox.begin()) {
    Serial.println("Unable to init the Atmel 1DB4E6 Sigfox chipset");
    return;
  }
  // Active l'utilisation de la LED orange de débug, qui clignote a l'envoi des données
  SigFox.debug();

  // Initialise le senseur DHT
  dht.begin();

  // Accroche le bouton d'envoi manuel
  pinMode(1, INPUT);
  LowPower.attachInterruptWakeup(1, SigFox_Data_Sender, PinStatus::RISING);
}

void loop() {
  SigFox_Data_Sender();
  GoToSleep();
}

void GoToSleep() {
  Serial.println("MKR FOX 1200 - Going in sleep");
  // Vide le tampon de la communication Série
  Serial.flush();
  Serial.end();

  // Part en veille profonde
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

  // Begins the SigFox packet
  SigFox.beginPacket();
  SigFox.write(temp);
  SigFox.write(hum);
  // Ends the SigFox packet and waits for server response
  int status = SigFox.endPacket(true);

  // Confirms the transmission of data
  if (status > 0) {
    Serial.println("No transmission");
  } else {
    Serial.println("Transmission ok");
  }

  Serial.println(SigFox.status(SIGFOX));
  Serial.println(SigFox.status(ATMEL));

  // Checks if we got a response from the server
  if (SigFox.parsePacket()) {
    std::stringstream message;

    // Reads the whole SigFox downlink buffer
    while (SigFox.available()) {
      // Remplis le nombre avec un 0 sur la droite pour correspondre à un octet
      message << std::setfill('0') 
              // Un octet = 2 caractères hexadécimaux
              << std::setw(2) 
              // transforme la lecture en hexadécimal
              << std::hex 
              // lis le prochain caractère
              << SigFox.read();
    }

    // remplace le paramètre de période par la valeur reçue
    message >> loopDelaySeconds;

  } else {
    // Informe l'utilisateur que nous n'avons pas reçu de réponse du serveur
    Serial.println("Could not get any response from the server");
    Serial.println("Check the Sigfox coverage in your area");
    Serial.println(
        "If you are indoor, check the 20 dB coverage or move near a window");
  }

  // Arrête le module SigFox
  SigFox.end();
}

void SigFox_Data_Sender() {
  // Lance la communication série
  Serial.begin(9600);

  // Initialise le module DHT
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
  // Sends the packet 
  sendPacket(temp, hum);
}
