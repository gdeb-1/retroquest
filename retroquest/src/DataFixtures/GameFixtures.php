<?php

namespace App\DataFixtures;

use App\Entity\Game;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class GameFixtures extends Fixture
{
    public const GAME_REFERENCE_PREFIX = 'game_';

    public function load(ObjectManager $manager): void
    {
        $consoles = ['NES', 'SNES', 'Sega Genesis', 'Nintendo 64', 'PlayStation', 'Game Boy', 'Sega Dreamcast', 'PlayStation 2'];
        
        $titles = [
            'Super Mario Bros.', 'Sonic the Hedgehog', 'The Legend of Zelda', 'Metroid', 'Castlevania',
            'Mega Man', 'Chrono Trigger', 'Final Fantasy', 'Resident Evil', 'Silent Hill',
            'Tomb Raider', 'Crash Bandicoot', 'Spyro the Dragon', 'Gran Turismo', 'Tekken',
            'Super Metroid', 'Street Fighter II', 'Mortal Kombat', 'Doom', 'Quake',
            'Pokemon Red', 'Pokemon Blue', 'Pokemon Gold', 'Pokemon Silver', 'Tetris',
            'Pac-Man', 'Donkey Kong', 'Space Invaders', 'Galaga', 'Asteroids',
            'GoldenEye 007', 'Super Mario 64', 'Mario Kart 64', 'Star Fox 64', 'Banjo-Kazooie',
            'Super Smash Bros.', 'Metal Gear Solid', 'Resident Evil 2', 'Silent Hill 2', 'Halo',
            'Grand Theft Auto III', 'Kingdom Hearts', 'Final Fantasy VII', 'Final Fantasy VIII', 'Final Fantasy IX',
            'Final Fantasy X', 'Tony Hawk\'s Pro Skater', 'Spider-Man', 'Syphon Filter', 'Twisted Metal',
            'Secret of Mana', 'EarthBound', 'F-Zero', 'Super Mario Kart', 'Donkey Kong Country',
            'Super Mario World', 'Contra', 'Double Dragon', 'Ninja Gaiden', 'The Legend of Zelda: Ocarina of Time',
            'The Legend of Zelda: Majora\'s Mask', 'The Legend of Zelda: A Link to the Past', 'Rayman', 'Driver', 'Silent Hill 3',
            'Resident Evil 3', 'Grand Theft Auto: Vice City', 'The Legend of Dragoon', 'Xenogears', 'Vagrant Story',
            'Parasite Eve', 'Dino Crisis', 'Ape Escape', 'Crash Team Racing', 'Medal of Honor',
            'Spyro 2: Ripto\'s Rage', 'Legacy of Kain: Soul Reaver', 'Chrono Cross', 'Marvel vs. Capcom', 'Phantasy Star',
            'Shinobi', 'OutRun', 'Streets of Rage', 'Golden Axe', 'Aladdin',
            'The Lion King', 'Earthworm Jim', 'NBA Jam', 'NFL Blitz', 'Madden NFL 98',
            'Diablo', 'Warcraft II', 'StarCraft', 'Age of Empires', 'Command & Conquer',
            'Half-Life', 'Grim Fandango', 'Myst', 'Doom II', 'Duke Nukem 3D'
        ];

        for ($i = 0; $i < 100; $i++) {
            $game = new Game();
            
            $title = $titles[$i] ?? 'Retro Game ' . ($i + 1);
            $game->setTitle($title);
            $game->setConsole($consoles[array_rand($consoles)]);
            $game->setReleaseYear(rand(1980, 2005));
            $game->setIsHidden(rand(1, 100) <= 5);
            
            $manager->persist($game);
            
            $this->addReference(self::GAME_REFERENCE_PREFIX . $i, $game);
        }

        $manager->flush();
    }
}
