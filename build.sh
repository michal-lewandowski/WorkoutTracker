#!/bin/bash

# Build script for Symfony Docker + Next.js

echo "🏗️  Building Symfony Docker + Next.js Application"
echo "=================================================="

# Build backend
echo "📦 Building Symfony Backend..."
docker compose build --pull --no-cache php

# Build frontend  
echo "⚛️  Building Next.js Frontend..."
docker compose build --pull --no-cache frontend

# Build reverse proxy
echo "🌐 Building Nginx Reverse Proxy..."
docker compose build --pull --no-cache nginx

echo "✅ Build completed successfully!"
echo ""
echo "🚀 To start the application:"
echo "   docker compose up"
echo ""
echo "🌍 Access your application:"
echo "   Frontend: http://localhost"
echo "   Backend API: http://localhost/api/"
echo "   Swagger UI: http://localhost:8081"
