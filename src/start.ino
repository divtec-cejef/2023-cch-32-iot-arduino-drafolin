#include <SigFox.h>

void setup() {
  Serial.begin(9600);
  while (!Serial) {};

  if (!SigFox.begin()) {
    Serial.println("Unable to init the Atmel 1DB4E6 Sigfox chipset");
    return;
  }
  SigFox.debug();

/*
TO DO...
*/
  
}

void loop()
{  
/*
TO DO...
*/
}
