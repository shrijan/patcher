import './spatial.scss'

import Select from 'nsw-design-system/src/components/select/select.js'

Drupal.behaviors.spatial = {
  attach: () => {
    once('spatial', '.spatial').forEach(element => {
      const mapboxglmap = element.querySelector('.mapboxgl-map')
      const mapbox = mapboxglmap ? JSON.parse(mapboxglmap.dataset.mapbox) : {}

      const geojson = mapbox.geojson
      const clustering = mapbox.clustering

      const bodyStyles = window.getComputedStyle(document.body)
      const brandColors = {
        blue: bodyStyles.getPropertyValue('--nsw-focus-light'),
        red: bodyStyles.getPropertyValue('--nsw-brand-accent'),
        dark: bodyStyles.getPropertyValue('--nsw-brand-dark'),
        supplementary: bodyStyles.getPropertyValue('--nsw-brand-supplementary')
      }

      const svgMarker = color => {
        const markerColor = brandColors[color] || Object.values(brandColors)[0]
        return '<svg xmlns="http://www.w3.org/2000/svg" height="41px" width="27px" viewBox="0 0 27 41"><defs><radialGradient id="shadowGradient"><stop offset="10%" stop-opacity="0.4"></stop><stop offset="100%" stop-opacity="0.05"></stop></radialGradient></defs><ellipse cx="13.5" cy="34.8" rx="10.5" ry="5.25" fill="url(#shadowGradient)"></ellipse><path fill="'+markerColor+'" d="M27,13.5C27,19.07 20.25,27 14.75,34.5C14.02,35.5 12.98,35.5 12.25,34.5C6.75,27 0,19.22 0,13.5C0,6.04 6.04,0 13.5,0C20.96,0 27,6.04 27,13.5Z"></path><path opacity="0.25" d="M13.5,0C6.04,0 0,6.04 0,13.5C0,19.22 6.75,27 12.25,34.5C13,35.52 14.02,35.5 14.75,34.5C20.25,27 27,19.07 27,13.5C27,6.04 20.96,0 13.5,0ZM13.5,1C20.42,1 26,6.58 26,13.5C26,15.9 24.5,19.18 22.22,22.74C19.95,26.3 16.71,30.14 13.94,33.91C13.74,34.18 13.61,34.32 13.5,34.44C13.39,34.32 13.26,34.18 13.06,33.91C10.28,30.13 7.41,26.31 5.02,22.77C2.62,19.23 1,15.95 1,13.5C1,6.58 6.58,1 13.5,1Z"></path><circle fill="white" cx="13.5" cy="13.5" r="5.5"></circle></svg>'
      }

      const type = mapboxglmap ? mapboxglmap.dataset.type : null
      if (type == 'gmap') {
        const center = {
          lat: -33.81,
          lng: 151.25
        }

        //@ts-ignore
        google.maps.importLibrary("maps").then(library => {
          const map = new library.Map(document.getElementById("spatialmap"), {
            zoom: parseInt(geojson.zoom),
            center
          })

          const markers = []
          const infoWindows = []
          const markerBounds = new google.maps.LatLngBounds()

          geojson.features.forEach(feature => {
            if (feature.properties.Latitude === null || feature.properties.Longitude === null) {
              return
            }
            const position = {
              lat: parseFloat(feature.properties.Latitude),
              lng: parseFloat(feature.properties.Longitude)
            }

            markerBounds.extend(position)

            const marker = new google.maps.Marker({
              position,
              title: feature.properties.title,
              id: feature.properties.id,
              icon: 'data:image/svg+xml;charset=UTF-8;base64,' + btoa(svgMarker(feature.properties.color))
            })
            marker.metadata = {
              type: 'point',
              id: feature.properties.id
            }

            if (clustering != 1) {
              marker.setMap(map)
            }

            let popupDescription = feature.properties.description.replace(/\n/g, '<br/>')

            if (window.innerWidth <= 576 && popupDescription.length > 100) {
              popupDescription = feature.properties.description.slice(0, 100) + '...'
              popupDescription = popupDescription.replace(/\n/g, '<br/>')
            }

            let content = '<b>'
            if (feature.properties.link) {
              content += '<a href="'+ feature.properties.link +'">'
            } else {
              content += '<p>'
            }
            content += feature.properties.title
            content += feature.properties.link ? '</a>' : '</p>'
            content += '</b><p>' + popupDescription + '</p>'

            const infoWindow = new google.maps.InfoWindow({
              content,
              ariaLabel: feature.properties.title,
              disableAutoPan: true,
              maxWidth: 300,
            })
            infoWindows.push(infoWindow)

            // open info window when marker is clicked
            marker.addListener("click", () => {
              const position = marker.getPosition()
              map.setCenter(position)
              map.panTo(position)

              infoWindows.forEach(infobox => {
                infobox.close(map, marker)
              })

              infoWindow.open(map, marker)
            })

            map.addListener('click', () => {
              if (infoWindow) {
                infoWindow.close()
              }
            })

            markers.push(marker)
          })

          if (mapbox.defaultConfig) {
            map.setCenter(center)
            map.setZoom(parseInt(geojson.zoom))
          } else if (geojson.features.length > 1) {
            map.fitBounds(markerBounds)
          } else if (geojson.features.length == 1) {
            const featureProperties = geojson.features[0].properties
            if (featureProperties.Latitude !== null && featureProperties.Longitude !== null) {
              map.setCenter({
                lat: parseFloat(featureProperties.Latitude),
                lng: parseFloat(featureProperties.Longitude)
              })
            }
            map.setZoom(16)
          }

          if (clustering == 1) {
            new markerClusterer.MarkerClusterer({ markers, map })
          }

          //go to pin after clicking on list item.
          const allListItems = element.querySelectorAll('.nsw-list-item')
          allListItems.forEach(listItem => {
            listItem.addEventListener('click', e => {
              if (e.target.closest('a')) {
                return
              }
              e.preventDefault()

              allListItems.forEach(loopListItem => {
                loopListItem.querySelector('.nsw-list-item__content').classList.remove('highlighted')
              })

              listItem.querySelector('.nsw-list-item__content').classList.add('clicked', 'highlighted')

              const prefix = mapbox.plot_method == 'postcode' ? 'p' : ''
              const position = {
                lat: parseFloat(listItem.dataset[prefix+'lng']),
                lng: parseFloat(listItem.dataset[prefix+'lat'])
              }

              map.setCenter(position)
              map.panTo(position)
              map.setZoom(12)
            })
          })

          //reset button functionality
          element.querySelector('#searchPageFiltersTop #edit-map-reset')?.addEventListener('click', e => {
            e.preventDefault()
            map.setCenter(center)
            map.panTo(center)
            map.setZoom(parseInt(geojson.zoom))
          })
          element.querySelector('#searchPageFilters #edit-map-reset')?.addEventListener('click', e => {
            e.preventDefault()
            map.setCenter(center)
            map.panTo(center)
            map.setZoom(parseInt(geojson.zoom))
          })
        })
      } else if (type == 'mapbox') {
        mapboxgl.accessToken = mapbox.token

        const center = [151.25, -33.81]

        const map = new mapboxgl.Map({
          container: 'spatialmap',
          style: mapbox.style,
          zoom: geojson.zoom,
          center,
          attributionControl: false
        })

        // Add geolocate control to the map.
        map.addControl(
          new mapboxgl.GeolocateControl({
            positionOptions: {
              enableHighAccuracy: true
            },
            // When active the map will receive updates to the device's location as it changes.
            trackUserLocation: true,
            // Draw an arrow next to the location dot to indicate which direction the device is heading.
            showUserHeading: true
          })
        )

        // Add navigation control to the map.
        map.addControl(new mapboxgl.NavigationControl())

        map.on('load', () => {
          if (clustering != 1) {
            geojson.features.forEach(marker => {
              let popup = ''
              if (marker.properties.type == 'eepa_provider') {
                popup = new mapboxgl.Popup({ offset: 25 }).setHTML(
                  '<p><b>Organisation:</b> ' +  marker.properties.organisation + ', </p>' +
                  '<p><b>Address:</b> ' + marker.properties.address + ', ' + marker.properties.suburb + '</p>' +
                  '<p><b>Phone:</b> ' + marker.properties.phone + '</p>'
                )
              } else if (marker.properties.type == 'ev_charging_station') {
                popup = new mapboxgl.Popup({ offset: 25 }).setHTML(
                  '<p>' + marker.properties.address +
                  '<p>' +  marker.properties.level + '</p>'
                )
              } else if (marker.properties.type == 'spatial_component') {
                let popupDescription = marker.properties.description.replace(/\n/g, '<br/>')
                if (window.innerWidth <= 576 && popupDescription.length > 100) {
                  popupDescription = marker.properties.description.slice(0, 100) + '...'
                  popupDescription = popupDescription.replace(/\n/g, '<br/>')
                }

                let content = '<b>'
                if (marker.properties.link) {
                  content += '<a href="'+ marker.properties.link +'">'
                } else {
                  content += '<p>'
                }
                content += marker.properties.title
                content += marker.properties.link ? '</a>' : '</p>'
                content += '</b><p>' + popupDescription + '</p>'
                popup = new mapboxgl.Popup({ offset: 25 }).setHTML(content)
              }

              const el = document.createElement('div')
              el.className = 'marker-icon'
              el.innerHTML = svgMarker(marker.properties.color)
              el.id = marker.properties.id
              el.addEventListener('focus', () => {
                map.setCenter(marker.geometry.coordinates)
              })

              new mapboxgl.Marker(el)
                .setLngLat(marker.geometry.coordinates)
                .setPopup(popup)
                .addTo(map)
            })
          } else {
            map.addSource('markers', {
              type: 'geojson',
              data: {
                type: "FeatureCollection",
                features: geojson.features
              },
              cluster: true,
              clusterMaxZoom: 14, // Max zoom to cluster points on
              clusterRadius: 50 // Radius of each cluster when clustering points (defaults to 50)
            })

            map.addLayer({
              id: 'clusters',
              type: 'circle',
              source: 'markers',
              filter: ['has', 'point_count'],
              paint: {
                'circle-color': [
                  'step',
                  ['get', 'point_count'],
                  brandColors.supplementary,
                  5,
                  brandColors.supplementary,
                  10,
                  brandColors.supplementary
                ],
                'circle-radius': [
                  'step',
                  ['get', 'point_count'],
                  20,
                  100,
                  30,
                  750,
                  40
                ]
              }
            })

            const textDark = bodyStyles.getPropertyValue('--nsw-text-dark')
            map.addLayer({
              id: 'cluster-count',
              type: 'symbol',
              source: 'markers',
              filter: ['has', 'point_count'],
              layout: {
                'text-field': ['get', 'point_count_abbreviated'],
                'text-font': ['DIN Offc Pro Medium', 'Arial Unicode MS Bold'],
                'text-size': 12
              },
              paint: {
                'text-color': textDark
              }
            })

            map.addLayer({
              id: 'unclustered-point',
              type: 'circle',
              source: 'markers',
              filter: ['!', ['has', 'point_count']],
              paint: {
                'circle-color': [
                  'match',
                  ['get', 'color'],
                  'blue',
                  brandColors.supplementary,
                  'red',
                  brandColors.red,
                  brandColors.supplementary // default.
                ],
                'circle-radius': 10,
                'circle-stroke-width': 1,
                'circle-stroke-color': brandColors.supplementary
              }
            })

            const clickOnClusterFeature = feature => {
              const clusterId = feature.properties.cluster_id
              map.getSource('markers').getClusterExpansionZoom(
                clusterId,
                (err, zoom) => {
                  if (err) {
                    return
                  }

                  map.easeTo({
                    center: feature.geometry.coordinates,
                    zoom
                  })
                }
              )
            }
            map.on('click', 'clusters', e => {
              const features = map.queryRenderedFeatures(e.point, {
                layers: ['clusters']
              })
              clickOnClusterFeature(features[0])
            })

            const clickOnUnclusteredFeature = (feature, lngLat) => {
              const coordinates = feature.geometry.coordinates.slice()
              const title = feature.properties.title
              const link = feature.properties.link
              const description = feature.properties.description

              if (lngLat !== undefined) {
                while (Math.abs(lngLat.lng - coordinates[0]) > 180) {
                  coordinates[0] += lngLat.lng > coordinates[0] ? 360 : -360
                }
              }

              new mapboxgl.Popup({ offset: 10 })
                .setLngLat(coordinates)
                .setHTML(
                  '<b><a href="'+ link +'">' + title + '</a></b>' +
                  '<p>' + description + '</p>'
                )
                .addTo(map)
            }
            map.on('click', 'unclustered-point', e => {
              clickOnUnclusteredFeature(e.features[0], e.lngLat)
            })

            let markers = []
            map.on('render', () => {
              let features = map.queryRenderedFeatures({
                layers: ['clusters', 'unclustered-point']
              })

              // Remove duplicates
              features = [...new Map(features.map(feature => [
                feature.id !== undefined ? feature.id : feature.properties.id,
                feature
              ])).values()]

              markers.forEach(marker => {
                marker.remove()
              })
              markers = features.map(feature => {
                const el = document.createElement('div')

                const size = (feature.layer.paint['circle-radius'] * 2).toString() + 'px'
                el.style.width = size
                el.style.height = size

                el.tabIndex = 0
                el.addEventListener('keydown', e => {
                  if (e.key != 'Enter') {
                    return
                  }
                  if (feature.id !== undefined) {
                    clickOnClusterFeature(feature)
                  } else {
                    clickOnUnclusteredFeature(feature)
                  }
                })
                el.addEventListener('focus', () => {
                  map.setCenter(feature.geometry.coordinates)
                })
                return new mapboxgl.Marker(el)
                  .setLngLat(feature.geometry.coordinates)
                  .addTo(map)
              })
            })
          }

          // go to filter bounds
          if (mapbox.filtered) {
            const bounds = []
            geojson.features.forEach(pin => {
              bounds.push(pin.geometry.coordinates)
            })

            if (mapbox.defaultConfig) {
              map.flyTo({
                center,
                essential: true,
                zoom: geojson.zoom
              })
            } else if (bounds.length > 0) {
              const lat = bounds.map(p => p[1])
              const lng = bounds.map(p => p[0])

              const min_coords = [
                Math.min.apply(null, lng),
                Math.min.apply(null, lat)
              ]
              const max_coords = [
                Math.max.apply(null, lng),
                Math.max.apply(null, lat)
              ]

              if (bounds.length > 1) {
                map.fitBounds([min_coords, max_coords], {
                  padding: 100
                })
              } else {
                map.flyTo({
                  center: min_coords,
                  essential: true,
                  zoom: 12
                })
              }
            }
          }
        })

        //go to pin after clicking on list item.
        const allListItems = element.querySelectorAll('.nsw-list-item')
        allListItems.forEach(listItem => {
          listItem.addEventListener('click', () => {
            allListItems.forEach(loopListItem => {
              loopListItem.querySelector('.nsw-list-item__content').classList.remove('highlighted')
            })

            listItem.querySelector('.nsw-list-item__content').classList.add('clicked', 'highlighted')
          })
        })

        //reset button functionality.
        element.querySelector('#searchPageFiltersTop #edit-map-reset')?.addEventListener('click', e => {
          e.preventDefault()
          map.flyTo({
            center,
            essential: true,
            zoom: geojson.zoom
          })
        })
        element.querySelector('#searchPageFilters #edit-map-reset')?.addEventListener('click', e => {
          e.preventDefault()
          map.flyTo({
            center,
            essential: true,
            zoom: geojson.zoom
          })
        })
      }

      const select = element.querySelector('#searchPageFiltersTop .checkboxes .js-form-type-select')
      if (select) {
        const customselect = new Select(select)
        customselect.init()
        customselect.allButton.remove()

        const li = document.createElement('li')
        li.hidden = customselect.getOptions()[2] == 0
        const a = document.createElement('a')
        a.href = 'javascript:void(0);'
        a.addEventListener('click', () => {
          customselect.getOptions()[0].forEach(option => {
            option.setAttribute('aria-selected', 'true')
            customselect.selectOption(option)
          })
        })
        a.innerText = 'Clear all selections'
        li.appendChild(a)
        customselect.list.appendChild(li)

        customselect.updateAllButton = () => {
          li.hidden = customselect.getOptions()[2] == 0
        }

        select.role = 'application'
      }

      const mobileFilterResults = element.querySelector('#search-result-filter .sc-filters-title')
      mobileFilterResults?.addEventListener('click', () => {
        mobileFilterResults.nextElementSibling.classList.toggle('nsw-display-none')
      });

      const FormScTop = element.querySelector('#views-exposed-form-sc-list-top')
      if (FormScTop) {
        const FormSc = element.querySelector('#views-exposed-form-spatial-components-block-list')
        if (FormSc) {
          const postcode = FormScTop.querySelector('.form-text-postcode')
          postcode.addEventListener('keyup', () => {
            FormSc.querySelector('.form-item-postcode input').value = postcode.value
          })
        }

        FormScTop.querySelector('.form-submit').addEventListener('click', () => {
          FormPub.submit()
        })
      }
    })

    once('spatial-tab', '.sc-tabs').forEach(element => {
      const preset_tab = localStorage.getItem('map-active-tab')
      if (preset_tab && preset_tab != element.querySelector('a.active').getAttribute('href')) {
        const a = element.querySelector('a[href="' + preset_tab + '"]')
        if (a) {
          a.click()
        }
      }

      element.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', e => {
          e.preventDefault()
          localStorage.setItem('map-active-tab', link.getAttribute('href'))
        })
      })
    })
  }
}
