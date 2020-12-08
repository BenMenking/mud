#####################################################
#  Welcome to the interactive builder. This program #
#  will simulate you as a player, you will be able  #
#  to walk between rooms as well as add them in     #
#  and see them from where you are.                 #
#####################################################

world = """
{
    "world-name": "{0}",
    "rooms": {
                {1}
    }
}
"""

class RoomBuilder():
    def __init__(self):
        self.currentRoom = None
        self.rooms = []
    def move(self):
        pass
    def addRoom(self, roomId, desc, spawn, temp, lightLevel, terrainType):
        self.rooms.append(roomId)
        roomId = Room(roomId, desc, spawn, temp, lightLevel, terrainType)


class Room():
    def __init__(self, roomId: str, desc: str, spawn: bool, temperature: int, lightLevel: int, terrainType: str):
        self.roomId = roomId
        self.exits = []
        self.description = desc
        self.spawn = spawn
        self.temperature = temperature
        self.lightLevel = lightLevel
        self.terrainType = terrainType
    def addExit(self, direction, target, flag, keywords, keyname):
        self.exits.append([direction, target, flag, keywords, keyname])



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
            "extras": []{6}
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