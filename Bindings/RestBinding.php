<?php
/**
 * File defining \Backend\Modules\Bindings\RestBinding
 *
 * PHP Version 5.3
 *
 * @category   Backend
 * @package    Modules
 * @subpackage Bindings
 * @author     J Jurgens du Toit <jrgns@backend-php.net>
 * @copyright  2011 - 2012 Jade IT (cc)
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 * @link       http://backend-php.net
 */
namespace Backend\Modules\Bindings;
use \Backend\Modules\Exception as ModuleException;
/**
 * Rest Connection Binding to get information from REST services
 *
 * @category   Backend
 * @package    Modules
 * @subpackage Bindings
 * @author     J Jurgens du Toit <jrgns@jrgns.net>
 * @license    http://www.opensource.org/licenses/mit-license.php MIT License
 * @link       http://backend-php.net
 */
class RestBinding extends ServiceBinding
{
    /**
     * The constructor for the object.
     *
     * @param array $settings The settings for the REST Connection
     */
    public function __construct(array $settings)
    {
        parent::__construct($settings);
        if (isset($settings['resource'])) {
            $this->setUrl($this->url . $settings['resource']);
        }
    }

    /**
     * Find multiple instances of the resource.
     *
     * Don't specify any criteria to retrieve a full list of instances.
     *
     * @param array $conditions An array of conditions on which to filter the list.
     * @param array $options    An array of options.
     *
     * @return array An array of representations of the resource.
     */
    public function find(array $conditions = array(), array $options = array())
    {
        $result = $this->execute();
        if (is_array($result)) {
            $result = array_map(array($this, 'mapResult'), $result);
        }
        return $result;
    }

    /**
     * Map the returned result to a new model of {@see $this->className}
     *
     * @param mixed $elm The element to map.
     *
     * @return \Backend\Interfaces\ModelInterface
     */
    protected function mapResult($elm)
    {
        if (!is_object($elm) && !is_array($elm)) {
            return $elm;
        }
        $elm = (array)$elm;
        $object = new $this->className;
        $object->populate($elm);
        return $object;
    }

    /**
     * Create an instance on the source, and return the instance.
     *
     * @param array $data The data to create a new resource.
     *
     * @return \Backend\Interfaces\ModelInterface The created model.
     * @throws \Backend\Modules\Exception When the resource can't be created.
     */
    public function create(array $data)
    {
    }

    /**
     * Read and return the single, specified instance of the resource.
     *
     * @param mixed $identifier The unique identifier for the instance, or an
     * array containing criteria on which to search for the resource.
     *
     * @return \Backend\Interfaces\ModelInterface The identified model.
     * @throws \Backend\Modules\Exception When the resource can't be found.
     */
    public function read($identifier)
    {
        $result = $this->get($identifier);
        $result = $this->mapResult($result);
        if ($result instanceof \Backend\Base\Models\BoundModel
            && $result->getId() === null
        ) {
            $result->setId($identifier);
        }
        return $result;
    }

    /**
     * Refresh the specified instance on the source.
     *
     * This function is the logical counterpart to update, and receives data from
     * the source.
     *
     * @param \Backend\Interfaces\ModelInterface &$model The model to refresh.
     * Passed by reference.
     *
     * @return boolean If the refresh was successful or not.
     * @throws \Backend\Modules\Exception When the resource can't be refreshed.
     */
    public function refresh(\Backend\Interfaces\ModelInterface &$model)
    {
    }

    /**
     * Update the specified instance of the resource.
     *
     * This function is the logical counterpart to refresh, and sends data to
     * the source.
     *
     * @param \Backend\Interfaces\ModelInterface &$model The model to update.
     * Passed by reference.
     *
     * @return boolean If the update was successful or not.
     * @throws \Backend\Modules\Exception When the resource can't be updated.
     */
    public function update(\Backend\Interfaces\ModelInterface &$model)
    {
    }

    /**
     * Delete the specified instance of the resource
     *
     * @param \Backend\Interfaces\ModelInterface &$model The model to delete
     *
     * @return boolean If the deletion was succesful or not.
     * @throws \Backend\Modules\Exception When the resource can't be deleted.
     */
    public function delete(\Backend\Interfaces\ModelInterface &$model)
    {
    }
}
