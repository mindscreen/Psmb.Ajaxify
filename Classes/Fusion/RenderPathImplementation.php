<?php
namespace Psmb\Ajaxify\Fusion;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

/**
 * Returns the path to the parent Fusion object
 */
class RenderPathImplementation extends AbstractFusionObject {

	/**
	 * @Flow\Inject
	 * @var \Neos\Cache\Frontend\VariableFrontend
	 */
	protected $pathsCache;

	/**
	 * Returns the part of the fusion path that calls the {prototypeName}, i.e.
	 * the key in `@process.{key} = Psmb.Ajaxify:Ajaxify`
	 * @param string $prototypeName
	 * @return string
	 */
	protected function getFusionPathKey($prototypeName = 'Psmb.Ajaxify:Ajaxify')
	{
		$pathParts = explode('/', $this->path);
		$prototypeLength = strlen($prototypeName);
		// look for "*<{prototypeName}>" segments
		while (count($pathParts)) {
			$last = array_pop($pathParts);
			if (substr($last, -$prototypeLength - 1, $prototypeLength) === $prototypeName) {
				// trim the <{prototypeName}> part
				return substr($last, 0, strlen($last) - $prototypeLength - 2);
			}
		}
		return '';
	}

	/**
	 * Retrieves the part of the path that contains the content that is to be
	 * rendered asynchronously
	 * @return string
	 */
	protected function getRenderPath()
	{
		$prototypeName = $this->fusionValue('prototypeName');
		$prototypeInPath = '<' . $prototypeName . '>';
		$pathParts = explode($prototypeInPath, $this->path);
		// we split everything up to .../__meta/process/{key}<prototypeName> and
		// need to remove the trailing segments
		return dirname($pathParts[0], 3);
	}

	/**
	 * Evaluates the `entryIdentifier` fusion value while providing the name of
	 * the fusion key defining the ajaxify rendering.
	 * @see getFusionPathKey
	 * @return string
	 */
	protected function getEntryIdentifier()
	{
		$prototypeName = $this->fusionValue('prototypeName');
		$key = $this->getFusionPathKey($prototypeName);
		$this->runtime->pushContext('key', $key);
		$entryIdentifier = $this->fusionValue('entryIdentifier');
		$this->runtime->popContext();
		return urlencode($entryIdentifier);
	}

	public function evaluate() {
		$cacheIdentifier = $this->getEntryIdentifier();
		$path = $this->getRenderPath();
		$this->pathsCache->set(
			$cacheIdentifier,
			$path
		);
		return $cacheIdentifier;
	}

}
