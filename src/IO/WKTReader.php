<?php

namespace Brick\Geo\IO;

use Brick\Geo\Point;
use Brick\Geo\LineString;
use Brick\Geo\Polygon;
use Brick\Geo\MultiPoint;
use Brick\Geo\MultiLineString;
use Brick\Geo\MultiPolygon;
use Brick\Geo\GeometryCollection;
use Brick\Geo\Exception\GeometryException;

/**
 * Builds geometries out of Well-Known Text strings.
 */
class WKTReader
{
    /**
     * @param string $wkt
     *
     * @return \Brick\Geo\Geometry
     *
     * @throws GeometryException
     */
    public function read($wkt)
    {
        $parser = new WKTParser(strtoupper($wkt));
        $geometry = $this->readGeometry($parser);

        if (! $parser->isEndOfStream()) {
            throw GeometryException::invalidWkt();
        }

        return $geometry;
    }

    /**
     * @param WKTParser $parser
     *
     * @return \Brick\Geo\Geometry
     *
     * @throws \Brick\Geo\Exception\GeometryException
     */
    public function readGeometry(WKTParser $parser)
    {
        $geometryType = $parser->getNextWord();
        $zm = $parser->getOptionalNextWord();

        if ($zm === null) {
            $is3D = false;
            $isMeasured = false;
        } elseif ($zm === 'Z') {
            $is3D = true;
            $isMeasured = false;
        } elseif ($zm === 'M') {
            $is3D = false;
            $isMeasured = true;
        } elseif ($zm === 'ZM') {
            $is3D = true;
            $isMeasured = true;
        } else {
            throw new GeometryException('Unexpected word in WKT: ' . $zm);
        }

        switch ($geometryType) {
            case 'POINT':
                return $this->readPointText($parser, $is3D, $isMeasured);
            case 'LINESTRING':
                return $this->readLineStringText($parser, $is3D, $isMeasured);
            case 'POLYGON':
                return $this->readPolygonText($parser, $is3D, $isMeasured);
            case 'MULTIPOINT':
                return $this->readMultiPointText($parser, $is3D, $isMeasured);
            case 'MULTILINESTRING':
                return $this->readMultiLineStringText($parser, $is3D, $isMeasured);
            case 'MULTIPOLYGON':
                return $this->readMultiPolygonText($parser, $is3D, $isMeasured);
            case 'GEOMETRYCOLLECTION':
                return $this->readGeometryCollectionText($parser, $is3D, $isMeasured);
        }

        throw new GeometryException('Unknown geometry type: ' . $geometryType);
    }

    /**
     * x y
     *
     * @param WKTParser $parser
     * @param boolean   $is3D
     * @param boolean   $isMeasured
     *
     * @return \Brick\Geo\Point
     */
    private function readPoint(WKTParser $parser, $is3D, $isMeasured)
    {
        $x = $parser->getNextNumber();
        $y = $parser->getNextNumber();

        $z = $is3D ? $parser->getNextNumber() : null;
        $m = $isMeasured ? $parser->getNextNumber() : null;

        return Point::factory($x, $y, $z, $m);
    }

    /**
     * (x y)
     *
     * @param WKTParser $parser
     * @param boolean   $is3D
     * @param boolean   $isMeasured
     *
     * @return \Brick\Geo\Point
     */
    private function readPointText(WKTParser $parser, $is3D, $isMeasured)
    {
        $parser->matchOpener();
        $point = $this->readPoint($parser, $is3D, $isMeasured);
        $parser->matchCloser();

        return $point;
    }

    /**
     * (x y, ...)
     *
     * @param WKTParser $parser
     * @param boolean   $is3D
     * @param boolean   $isMeasured
     *
     * @return \Brick\Geo\Point[]
     */
    private function readMultiPoint(WKTParser $parser, $is3D, $isMeasured)
    {
        $parser->matchOpener();
        $points = [];

        do {
            $points[] = $this->readPoint($parser, $is3D, $isMeasured);
            $nextToken = $parser->getNextCloserOrComma();
        } while ($nextToken === ',');

        return $points;
    }

    /**
     * (x y, ...)
     *
     * @param WKTParser $parser
     * @param boolean   $is3D
     * @param boolean   $isMeasured
     *
     * @return \Brick\Geo\LineString
     */
    private function readLineStringText(WKTParser $parser, $is3D, $isMeasured)
    {
        $points = $this->readMultiPoint($parser, $is3D, $isMeasured);

        return LineString::factory($points);
    }

    /**
     * (x y, ...)
     *
     * @param WKTParser $parser
     * @param boolean   $is3D
     * @param boolean   $isMeasured
     *
     * @return \Brick\Geo\MultiPoint
     */
    private function readMultiPointText(WKTParser $parser, $is3D, $isMeasured)
    {
        $points = $this->readMultiPoint($parser, $is3D, $isMeasured);

        return MultiPoint::factory($points);
    }

    /**
     * ((x y, ...), ...)
     *
     * @param WKTParser $parser
     * @param boolean   $is3D
     * @param boolean   $isMeasured
     *
     * @return \Brick\Geo\LineString[]
     */
    private function readMultiLineString(WKTParser $parser, $is3D, $isMeasured)
    {
        $parser->matchOpener();
        $lineStrings = [];

        do {
            $lineStrings[] = $this->readLineStringText($parser, $is3D, $isMeasured);
            $nextToken = $parser->getNextCloserOrComma();
        } while ($nextToken === ',');

        return $lineStrings;
    }

    /**
     * ((x y, ...), ...)
     *
     * @param WKTParser $parser
     * @param boolean   $is3D
     * @param boolean   $isMeasured
     *
     * @return \Brick\Geo\Polygon
     */
    private function readPolygonText(WKTParser $parser, $is3D, $isMeasured)
    {
        $rings = $this->readMultiLineString($parser, $is3D, $isMeasured);

        return Polygon::factory($rings);
    }

    /**
     * ((x y, ...), ...)
     *
     * @param WKTParser $parser
     * @param boolean   $is3D
     * @param boolean   $isMeasured
     *
     * @return \Brick\Geo\MultiLineString
     */
    private function readMultiLineStringText(WKTParser $parser, $is3D, $isMeasured)
    {
        $rings = $this->readMultiLineString($parser, $is3D, $isMeasured);

        return MultiLineString::factory($rings);
    }

    /**
     * (((x y, ...), ...), ...)
     *
     * @param WKTParser $parser
     * @param boolean   $is3D
     * @param boolean   $isMeasured
     *
     * @return \Brick\Geo\MultiPolygon
     */
    private function readMultiPolygonText(WKTParser $parser, $is3D, $isMeasured)
    {
        $parser->matchOpener();
        $polygons = [];

        do {
            $polygons[] = $this->readPolygonText($parser, $is3D, $isMeasured);
            $nextToken = $parser->getNextCloserOrComma();
        } while ($nextToken === ',');

        return MultiPolygon::factory($polygons);
    }

    /**
     * @param WKTParser $parser
     * @param boolean   $is3D
     * @param boolean   $isMeasured
     *
     * @return \Brick\Geo\GeometryCollection
     *
     * @throws GeometryException
     */
    private function readGeometryCollectionText(WKTParser $parser, $is3D, $isMeasured)
    {
        $parser->matchOpener();
        $geometries = [];

        do {
            $geometry = $this->readGeometry($parser);

            if ($geometry->is3D() !== $is3D || $geometry->isMeasured() !== $isMeasured) {
                throw GeometryException::collectionDimensionalityMix($is3D, $isMeasured, $geometry);
            }

            $geometries[] = $geometry;
            $nextToken = $parser->getNextCloserOrComma();
        } while ($nextToken === ',');

        return GeometryCollection::factory($geometries);
    }
}