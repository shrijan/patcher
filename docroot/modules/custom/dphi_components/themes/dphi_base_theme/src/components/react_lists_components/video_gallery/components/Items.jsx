import { useEffect, useState } from 'react'
import VideoModal from './VideoModal.jsx'

export default function Items({ currentItems, theme }) {
  const [selectedItem, setSelectedItem] = useState(null)
  const [data, setData] = useState({})

  useEffect(() => {
    currentItems.forEach(item => {
      const url = item.video_data?.url
      if (!url || data[url]) {
        return
      }
      if (url.startsWith('https://vimeo.com/')) {
        fetch('https://vimeo.com/api/oembed.json?url='+url).then(response => {
          if (response.ok) {
            response.json().then(json => {
              setData(prevData => {
                return {
                  ...prevData,
                  [url]: {
                    'iframe': json['html'].split('src="')[1].split('"')[0],
                    'img': json['thumbnail_url'],
                  }
                }
              })
            })
          }
        })
      } else {
        setData(prevData => {
          return {
            ...prevData,
            [url]: {
              'iframe': item.video_data.embed,
              'img': 'https://img.youtube.com/vi/'+item.video_data.youtube_id+'/0.jpg',
            }
          }
        })
      }
    })
  }, [JSON.stringify(currentItems)])

  const openModal = item => {
    setSelectedItem(item)
  }

  const closeModal = () => {
    setSelectedItem(null)
  }

  const getThemeClasses = (theme) => {
    switch (theme) {
      case 'light':
        return 'nsw-bg--brand-light';
      case 'dark':
        return 'nsw-bg--brand-dark nsw-text--light';
      default:
        return '';
    }
  }

  return (currentItems.length == 0 ? <>No results were found</> :
    <>
      {selectedItem && data[selectedItem.video_data?.url] && <VideoModal item={{
        ...selectedItem,
        iframe: data[selectedItem.video_data.url].iframe
      }} onClose={closeModal} theme={theme} />}
      <div className="nsw-grid video-grid">
        {Array.from({ length: Math.ceil(currentItems.length / 3) }, (_, i) => (
          <div key={i} className="nsw-row video-row">
            {currentItems.slice(i * 3, (i + 1) * 3).map((item, index) => (
              <div
                key={item.id}
                className="nsw-col nsw-col-md-4 video-item"
                onKeyDown={e => {
                  if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault()
                    openModal(item)
                  }
                }}
                tabIndex="0"
                role="button"
                aria-label={`Open video: ${item.title}`}
              >
                <figure className="nsw-media nsw-media--true">
                  {data[item.video_data?.url] && <div
                    className="nsw-media__thumbnail"
                    onClick={() => openModal(item)}
                  >
                    <img
                      src={data[item.video_data.url].img}
                      alt={item.title}
                    />
                    <div className="play-icon">
                      <span className="material-icons nsw-material-icons">
                        play_arrow
                      </span>
                    </div>
                  </div>}
                  <figcaption className={`video-caption ${getThemeClasses(theme)}`}>
                    <strong>{item.title}</strong>
                    {item.caption && <div dangerouslySetInnerHTML={{ __html: item.caption }} />}
                    {item.transcript && <a href={item.transcript} className={theme == 'light' && 'nsw-text--brand-dark'}>View transcript</a>}
                  </figcaption>
                </figure>
              </div>
            ))}
          </div>
        ))}
      </div>
    </>
  )
}
