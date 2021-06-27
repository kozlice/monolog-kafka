#!/bin/bash

set -e

SCALA_VERSION=$1
KAFKA_VERSION=$2
PREFIXDIR=$3

if [[ $PREFIXDIR != /* ]]; then
    PREFIXDIR="$PWD/$PREFIXDIR"
fi

mkdir -p "$PREFIXDIR/kafka"
pushd "$PREFIXDIR/kafka"

curl -sL "https://downloads.apache.org/kafka/${KAFKA_VERSION}/kafka_${SCALA_VERSION}-${KAFKA_VERSION}.tgz" | \
    tar -xz --strip-components=1 -f -

popd
