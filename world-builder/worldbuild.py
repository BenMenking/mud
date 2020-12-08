
world = """
{
    "world-name": "Tikal",
    "rooms": {
                {0}
    }
}
"""
def startGen():
    roomName = input('What do you want to name the room?')
    spawn = bool(input('Is this room a spawn (true/false)'))
    description = input('Please describe the room in detail to me')
    temperature = input('What is the temperature of this room in degF')
    lightLevel = input('How bright is this room')
    terrainType = input('What kind of terrain is this: [INSIDE|FIELD|CITY|FOREST|HILLS|MOUNTAINS|WATER_SWIM|WATER_NOSWIM|UNDERWATER|FLYING]')






REFERENCE = """
{
    "room-name": "{0}",
    "spawn": "{1}",
    "description": "{2}",
    "exits" => {
        {3}
    },
    "temperature": {4},
    "oxygen_level": {5},
    "light_level": {6},
    "env": [
           "{7}"
     ],
     "terrain": "{8}"
}
"""

EXITS = """
"{0}": {
            "target": "{1}",
            "description": "{2}",
            "flag": "{3}",
            "keywords": [
                {4}
            ],
            "key-name": "{5}",
            "extras": [],
"""
#room-name is a string, replacing empty space with _
#spawn is a bool that states whether it is the world spawn or not
#description is a string that describes the room you're in
#flag is whether the room is unrestricted or not
#dir is a full cardinal direction [south, north, east, west]
#target is the room the direction goes to
#key-name is the id of the key that opens the door, or otherwise blank
#keywords is blank for now
#temperature is the temperature in Farenheigt
#oxygen_level is the level of o2 in the room 0-100
#light_level is the level of light in the room 50 being normal, 0 being pitch black, 100 being blindingly bright
#env is the environment type <DARK|DEATH|NOMOB|INDOORS|PEACEFUL|SOUNDPROOF|NOTRACK|NOMAGIC|TUNNEL|PRIVATE|GODROOM>
#terrain is the terrain type <INSIDE|FIELD|CITY|FOREST|HILLS|MOUNTAINS|WATER_SWIM|WATER_NOSWIM|UNDERWATER|FLYING>

#reference 4 needs to be able to contain multiple comma-delimited strings