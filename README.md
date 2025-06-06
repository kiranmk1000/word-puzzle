# Word Puzzle Game API

A Laravel-based API for a word puzzle game where players can generate puzzles, submit words, and compete on a leaderboard.

## Features

-   Generate 14-letter puzzles containing valid words
-   Submit words and validate them against the puzzle letters
-   Track student scores and maintain a leaderboard
-   View valid words for each puzzle
-   Get student submissions and total scores

## Technical Stack

-   PHP 8.1+
-   Laravel 10.x
-   MySQL/MariaDB
-   RESTful API architecture

## API Endpoints

### Puzzle Management

-   `POST /api/generate-puzzle` - Generate a new puzzle
-   `GET /api/puzzles/{puzzle}/valid-words` - Get valid words for a puzzle

### Student Management

-   `GET /api/students/{student}/submissions` - Get student's word submissions
-   `GET /api/students/{student}/score` - Get student's total score

### Word Submission

-   `POST /api/submit-word` - Submit a word for scoring
-   `GET /api/leaderboard` - Get the current leaderboard

## Setup Instructions

1. Clone the repository:

```bash
git clone [repository-url]
cd word-puzzle
```

2. Install dependencies:

```bash
composer install
```

3. Configure environment:

```bash
cp .env.example .env
php artisan key:generate
```

4. Configure database in `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=word_puzzle
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. Run migrations:

```bash
php artisan migrate
```

6. Start the server:

```bash
php artisan serve
```

## Testing

Run the test suite:

```bash
php artisan test
```

## Project Structure

-   `app/Services/` - Core business logic
    -   `PuzzleService.php` - Puzzle generation and validation
    -   `WordSubmissionService.php` - Word submission and scoring
-   `app/Http/Controllers/` - API endpoints
-   `app/Models/` - Database models
-   `tests/` - Test cases

## Technical Decisions

1. **Service-Oriented Design**

    - Separated business logic into services
    - Improved testability and maintainability
    - Clear separation of concerns

2. **Performance Optimizations**

    - Efficient word validation algorithm
    - Database indexing for faster queries
    - Caching of dictionary words

3. **Error Handling**
    - Comprehensive error messages
    - Proper HTTP status codes
    - Detailed logging

## License

This project is licensed under the MIT License.
