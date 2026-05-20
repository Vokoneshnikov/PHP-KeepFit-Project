CREATE TYPE gender AS ENUM ('male', 'female');

-- sedentary (1.2), light (1.375), moderate (1.55), active (1.725), very_active (1.9)
CREATE TYPE activity_level AS ENUM ('sedentary', 'light', 'moderate', 'active', 'very_active');

-- Цели пользователя
CREATE TYPE fitness_goal AS ENUM ('lose', 'maintain', 'gain');

-- Типы приемов пищи
CREATE TYPE meal_type AS ENUM ('breakfast', 'lunch', 'dinner', 'other');