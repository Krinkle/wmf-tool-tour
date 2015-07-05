<?php
/**
 * Main class
 *
 * @package wmf-tool-tour
 */

class TourTool extends KrToolBaseClass {
	protected static $labels = array(
		// Pages visible to the public. Editing disabled.
		// Part of Wikimedia unified login.
		'closed',

		// Authorised access only. Editing and reading restricted.
		// Not part of Wikimedia unified login.
		'private',

		// Pages visible to the public. Editing restricted.
		// Not part of Wikimedia unified login.
		'fishbowl',
	);

	protected function show() {
		global $kgBase;

		$kgBase->setHeadTitle( 'Home' );
		$kgBase->setLayout( 'header', array(
			'titleText' => 'Wiki Tour',
			'captionHtml' => 'Manage a tour that passes by all Wikimedia wikis.',
		) );

		$kgBase->addOut( '<div class="container">' );

		$kgBase->addOut( kfAlertHtml( 'info', '<strong>Welcome!</strong> Hello there.' ) );

		// TODO: Fetch filter from wiki page config
		$filter = array( 'private' => false );

		$wikis = $this->getWikis( $filter );
		$table = '<table class="table table-striped table-hover" x-data-toggle="table"><thead><tr>'
			. '<th>Wiki</th>'
			. '<th data-sortable=1>Identifier</th>'
			. '<th data-sortable=1>Project</th>';
		$labels = array();
		foreach ( self::$labels as $label ) {
			if ( !( isset( $filter[$label] ) && $filter[$label] === false ) ) {
				$labels[] = $label;
				$table .= Html::element( 'th', array( 'data-sortable' => true ), ucfirst( $label ) );
			}
		}
		$table .= '<th data-sortable=1>Status</th>';
		$table .= '</tr></thead><tbody>';

		$stati = array( false, 'done', 'progress' );
		foreach ( $wikis as $dbname => $wiki ) {
			// TODO: Fetch status from wiki page
			$status = $stati[ array_rand( $stati ) ];
			$table .= '<tr>'
				. Html::rawElement( 'td', array(), Html::element( 'a', array(
					'href' => $wiki['url'],
				), $wiki['servername'] ) )
				. Html::element( 'td', array(), $dbname )
				. Html::element( 'td', array(), $wiki['project'] );
			foreach ( $labels as $label ) {
				$table .= Html::element( 'td', array(), $wiki[$label] ? ucfirst( $label ) : '' );
			}
			$table .= Html::rawElement( 'td', array(
					'class' => $this->getStatusClass( $status ),
				),
				$this->getStatusField( $status )
				. ' '
				. htmlspecialchars( $this->getStatusLabel( $status ) )
			);
			$table .= '</tr>';
		}
		$table .= '</tbody></table>';
		$kgBase->addOut($table);


		// Close container
		$kgBase->addOut( '</div>' );
	}

	protected function getStatusField( $status ) {
		if ( $status === 'done' ) {
			return '<span class="glyphicon glyphicon-ok" aria-hidden="true"></span>';
		}
		if ( $status === 'progress' ) {
			return '<span class="glyphicon glyphicon-retweet" aria-hidden="true"></span>';
		}
		return '<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>';
	}
	protected function getStatusLabel( $status ) {
		if ( $status === 'done' ) {
			return 'Done';
		}
		if ( $status === 'progress' ) {
			return 'In-progress';
		}
		return 'Needs volunteer';
	}
	protected function getStatusClass( $status ) {
		if ( $status === 'done' ) {
			return 'success';
		}
		if ( $status === 'progress' ) {
			return 'warning';
		}
		return 'info';
	}

	/**
	 * @return stdClass|bool
	 */
	protected function fetchSiteMatrix() {
		$data = kfApiRequest( 'https://meta.wikimedia.org', array(
			'action' => 'sitematrix',
			'smlangprop' => 'site',
			'smsiteprop' => 'url|dbname|code',
		) );
		// Sample:
		/*
			"286": {
				"code": "zu",
				"name": "isiZulu",
				"site": [
					{
						"url": "http://zu.wikipedia.org",
						"dbname": "zuwiki",
						"sitename": "Wikipedia"
					},
					{
						"url": "http://zu.wiktionary.org",
						..
					}
				]
			},
			"specials": [
				{
					"url": "http://advisory.wikimedia.org",
				},
		*/
		return $data ? $data->sitematrix : false;
	}

	/**
	 * @return stdClass
	 * @throws Exception If fetch failed
	 */
	protected function getSiteMatrix() {
		global $kgCache;
		$key = kfCacheKey( 'sitematrix', 2 );
		$value = $kgCache->get( $key );
		if ( $value === false ) {
			$value = $this->fetchSiteMatrix();
			if ( $value !== false ) {
				$kgCache->set( $key, $value, 3600 );
			} else {
				$value = null;
				// Don't try again for another minute
				$kgCache->set( $key, $value, 60 );
			}
		}
		if ( $value === null ) {
			throw new Exception( 'Fetch sitematrix failed' );
		}
		return $value;
	}

	/**
	 * @param array $filter Optional boolean filters for 'closed', 'private'
	 *  and/or 'fishbowl'. E.g. "array( 'private' => false )" to omit closed wikis.
	 * @return array
	 */
	protected function getWikis( array $filter = array() ) {
		$siteMatrix = $this->getSiteMatrix();
		$wikis = array();
		foreach ( $siteMatrix as $groupKey => $group ) {
			if ( $groupKey === 'count' ) {
				continue;
			}
			if ( $groupKey === 'specials' ) {
				$sites = $group;
			} else {
				// Language group
				$sites = $group->site;
			}
			foreach ( $sites as $site ) {
				$wiki = array(
					'url' => $site->url,
					'servername' => preg_replace( '#^https?://#', '', $site->url ),
					'project' => $groupKey === 'specials'
						? $groupKey
						: ( $site->code === 'wiki' ? 'wikipedia' : $site->code ),
				);
				foreach ( self::$labels as $label ) {
					$wiki[ $label ] = isset( $site->$label );
					if ( isset( $filter[$label] ) && $filter[$label] !== $wiki[ $label ] ) {
						continue 2;
					}
				}
				$wikis[ $site->dbname ] = $wiki;
			}
		}
		return $wikis;
	}
}
