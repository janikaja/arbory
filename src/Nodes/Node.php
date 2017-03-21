<?php

namespace CubeSystems\Leaf\Nodes;

use Alsofronie\Uuid\UuidModelTrait;
use CubeSystems\Leaf\Pages\PageInterface;

/**
 * Class Node
 * @package CubeSystems\Leaf
 */
class Node extends \Baum\Node
{
    use UuidModelTrait;
    
    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'content_type',
        'content_id',
        'item_position',
        'active',
        'locale',
    ];

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->name;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo|PageInterface
     */
    public function content()
    {
        return $this->morphTo();
    }

    /**
     * @return static[]
     */
    public function parents()
    {
        return $this->newQuery()
            ->where( $this->getLeftColumnName(), '<', (int) $this->getLeft() )
            ->where( $this->getRightColumnName(), '>', (int) $this->getRight() )
            ->orderBy( $this->getDepthColumnName(), 'asc' )
            ->get();
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->content_type;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param array $models
     * @return NodeCollection
     */
    public function newCollection( array $models = array() )
    {
        return new NodeCollection( $models );
    }

    /**
     * @return string
     */
    public function getUri()
    {
        $uri = [ ];

        foreach( $this->parents() as $parent )
        {
            $uri[] = $parent->getSlug();
        }

        $uri[] = $this->getSlug();

        return implode( '/', $uri );
    }

    /**
     * @param $name
     * @param array $parameters
     * @param bool $absolute
     * @return string
     */
    public function getUrl( $name, array $parameters = [], $absolute = true )
    {
        return route( 'node.' . $this->getKey() . '.' . $name, $parameters, $absolute );
    }
}
