CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(256) UNIQUE NOT NULL,
    password_hash VARCHAR(256) NOT NULL,
    name VARCHAR(100) NOT NULL,
    gender gender NOT NULL,
    birth_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_parameters (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    weight DECIMAL(3,2) NOT NULL,
    height SMALLINT NOT NULL,
    activity_factor activity_level NOT NULL,
    goal fitness_goal NOT NULL,
    measured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE daily_norms (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    calories INTEGER NOT NULL,
    proteins REAL NOT NULL,
    fats REAL NOT NULL,
    carbs REAL NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE foods (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    calories REAL NOT NULL, -- ккал на 100г
    proteins REAL DEFAULT 0,
    fats REAL DEFAULT 0,
    carbs REAL DEFAULT 0,
    created_by INTEGER REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE meals (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    food_id INTEGER NOT NULL REFERENCES foods(id) ON DELETE RESTRICT,
    amount_grams INTEGER NOT NULL CHECK (amount_grams > 0),
    type meal_type NOT NULL,
    consumed_at DATE NOT NULL DEFAULT CURRENT_DATE
);