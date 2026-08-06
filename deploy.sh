#!/bin/bash
# 一键部署脚本
# 用法: bash deploy.sh [registry/name:tag]
# 示例: bash deploy.sh                    # 仅本地启动
#       bash deploy.sh myrepo/app:v1      # 构建并推送镜像

set -e

IMAGE="${1:-video-analysis:latest}"
REGISTRY_PUSH=false

# 如果指定了远程镜像名，则推送
if [[ "$1" == *"/"* ]]; then
    REGISTRY_PUSH=true
fi

echo "========================================"
echo "  Video Analysis - Deploy"
echo "========================================"

# 1. 检查 .env
if [ ! -f .env ]; then
    echo "[!] .env 不存在，从 .env.example 复制"
    cp .env.example .env
    echo "    请编辑 .env 配置后再运行"
    exit 1
fi

# 2. 构建镜像
echo "[1/3] 构建 Docker 镜像: $IMAGE"
docker build -t "$IMAGE" .

# 3. 推送到仓库（可选）
if $REGISTRY_PUSH; then
    echo "[2/3] 推送镜像到仓库..."
    docker push "$IMAGE"
    echo "     镜像已推送: $IMAGE"
else
    echo "[2/3] 跳过推送（未指定仓库）"
fi

# 4. 启动
echo "[3/3] 启动服务..."
docker compose down 2>/dev/null || true
docker compose up -d

echo ""
echo "========================================"
echo "  部署完成！"
echo "  访问: http://localhost"
echo "  API:  http://localhost/api/v1/health"
echo "========================================"
