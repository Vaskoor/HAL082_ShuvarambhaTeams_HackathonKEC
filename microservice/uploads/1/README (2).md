# RAG Chatbot System

A complete **Retrieval-Augmented Generation (RAG)** chatbot system built with **Laravel**, **Python/FastAPI**, and **Ollama** for local LLM inference. The system allows you to upload PDF documents, create AI chatbots trained on those documents, and embed chat widgets on any website.

## Architecture Overview

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   Website       │────▶│  Laravel Backend │────▶│  Python RAG     │
│   (Widget)      │◄────│  (PHP)           │◄────│  (FastAPI)      │
└─────────────────┘     └──────────────────┘     └─────────────────┘
                               │                           │
                               ▼                           ▼
                        ┌──────────────┐          ┌─────────────────┐
                        │   MySQL      │          │   FAISS Vector  │
                        │   Database   │          │   Store         │
                        └──────────────┘          └─────────────────┘
                                                               │
                                                               ▼
                                                      ┌─────────────────┐
                                                      │   Ollama LLM    │
                                                      │   (Local AI)    │
                                                      └─────────────────┘
```

## Features

- **PDF Document Management**: Upload PDFs, automatic text extraction and chunking
- **Vector Embeddings**: Sentence-transformers for document embeddings
- **FAISS Vector Store**: Fast similarity search for document retrieval
- **Local LLM Integration**: Ollama for privacy-focused AI responses
- **Embeddable Widget**: JavaScript widget for any website
- **Multi-Chatbot Support**: Create multiple chatbots with different document sets
- **Conversation History**: Persistent threads for users and guests
- **Domain Whitelist**: Security control for widget embedding
- **Guest & User Support**: Works for both authenticated and anonymous users

## Quick Start

### Prerequisites

- Docker & Docker Compose
- Git

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd rag-chatbot-system
   ```

2. **Start all services**
   ```bash
   docker-compose up -d
   ```

3. **Install Laravel dependencies**
   ```bash
   docker-compose exec laravel composer install
   ```

4. **Run Laravel migrations**
   ```bash
   docker-compose exec laravel php artisan migrate
   ```

5. **Generate Laravel app key**
   ```bash
   docker-compose exec laravel php artisan key:generate
   ```

6. **Pull Ollama model**
   ```bash
   docker-compose exec ollama ollama pull llama2
   ```

### Access Points

| Service | URL | Description |
|---------|-----|-------------|
| Laravel API | http://localhost:8000 | Main API backend |
| Python RAG | http://localhost:8001 | RAG service docs |
| Ollama | http://localhost:11434 | LLM service |

## API Documentation

### Widget Endpoints (Public)

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/widget/send-message` | POST | Send message to chatbot |
| `/api/widget/history` | GET | Get conversation history |
| `/api/widget/config/{id}` | GET | Get chatbot configuration |
| `/api/widget/health` | GET | Health check |

### Admin Endpoints (Authenticated)

#### Files
- `GET /api/files` - List all files
- `POST /api/files/upload` - Upload PDF
- `POST /api/files/{id}/vectorize` - Vectorize file
- `DELETE /api/files/{id}` - Delete file

#### Chatbots
- `GET /api/chatbots` - List chatbots
- `POST /api/chatbots` - Create chatbot
- `GET /api/chatbots/{id}` - Get chatbot
- `PUT /api/chatbots/{id}` - Update chatbot
- `DELETE /api/chatbots/{id}` - Delete chatbot
- `POST /api/chatbots/{id}/files` - Add files to chatbot

#### Threads & Messages
- `GET /api/threads/user` - List user's threads
- `GET /api/chatbots/{id}/threads` - List chatbot threads
- `GET /api/threads/{id}/messages` - Get thread messages

## Widget Integration

Add the chatbot widget to any website:

```html
<!-- Add the widget script -->
<script src="https://your-api.com/widget/rag-widget.js"></script>

<!-- Initialize the widget -->
<script>
  RAGWidget.init({
    chatbotId: 1,
    apiUrl: 'https://your-api.com',
    position: 'bottom-right',
    primaryColor: '#3B82F6'
  });
</script>
```

### Widget Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `chatbotId` | number | required | Chatbot ID |
| `apiUrl` | string | required | API base URL |
| `userId` | number | null | User ID for authenticated users |
| `position` | string | 'bottom-right' | Widget position |
| `primaryColor` | string | '#3B82F6' | Theme color |
| `welcomeMessage` | string | auto | Custom welcome message |

## Database Schema

### Tables

- **rag_files**: Uploaded PDF files
- **chatbots**: Chatbot configurations
- **chatbot_rag_files**: Many-to-many relationship
- **chat_threads**: Conversation threads
- **chat_messages**: Individual messages

See `laravel-backend/database/migrations/` for full schema details.

## Configuration

### Environment Variables

#### Laravel (.env)
```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=rag_chatbot
DB_USERNAME=rag_user
DB_PASSWORD=rag_password

RAG_SERVICE_URL=http://python-rag:8000
OLLAMA_URL=http://ollama:11434
```

#### Python RAG (.env)
```env
OLLAMA_URL=http://ollama:11434
OLLAMA_MODEL=llama2
EMBEDDING_MODEL=sentence-transformers/all-MiniLM-L6-v2
VECTOR_STORE_PATH=./vector_store
```

## Development

### Local Development (without Docker)

#### Laravel Backend
```bash
cd laravel-backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

#### Python RAG Backend
```bash
cd python-rag-backend
python -m venv venv
source venv/bin/activate  # Windows: venv\Scripts\activate
pip install -r requirements.txt
uvicorn main:app --reload
```

### Running Tests

```bash
# Laravel tests
docker-compose exec laravel php artisan test

# Python tests
docker-compose exec python-rag pytest
```

## Customization

### Changing the LLM Model

1. Pull a new model in Ollama:
   ```bash
   docker-compose exec ollama ollama pull mistral
   ```

2. Update `OLLAMA_MODEL` in environment variables

3. Restart services:
   ```bash
   docker-compose restart python-rag
   ```

### Adjusting Chunk Size

Edit `python-rag-backend/.env`:
```env
CHUNK_SIZE=1024
CHUNK_OVERLAP=100
```

### Custom Embedding Model

```env
EMBEDDING_MODEL=sentence-transformers/all-mpnet-base-v2
```

## Troubleshooting

### Common Issues

**Ollama model not found**
```bash
docker-compose exec ollama ollama pull llama2
```

**Permission errors with storage**
```bash
docker-compose exec laravel chown -R www-data:www-data storage
```

**Vector store issues**
```bash
# Clear vector store
docker-compose exec python-rag rm -rf /app/vector_store/*
```

### Logs

```bash
# Laravel logs
docker-compose logs -f laravel

# Python RAG logs
docker-compose logs -f python-rag

# Ollama logs
docker-compose logs -f ollama
```

## Production Deployment

1. **Update environment variables** for production
2. **Enable SSL** with Nginx
3. **Set up proper CORS** configuration
4. **Configure backups** for MySQL and vector store
5. **Set up monitoring** and alerting

See `docker-compose.yml` for production profile with Nginx.

## Technology Stack

| Component | Technology |
|-----------|------------|
| Backend API | Laravel 12 (PHP 8.2) |
| RAG Service | FastAPI (Python 3.11) |
| Database | MySQL 8.0 |
| Vector DB | FAISS |
| Embeddings | sentence-transformers |
| LLM | Ollama (local) |
| Cache | Redis 7 |
| Container | Docker |

## License

MIT License - see LICENSE file for details.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## Support

For issues and questions, please use the GitHub issue tracker.
