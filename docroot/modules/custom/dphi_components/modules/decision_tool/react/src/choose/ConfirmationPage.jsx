export default function ConfirmationPage({step, admin, id, data}) {
  return <>
    <div className="decisionToolTitle">
      <h1>{step}. Your next steps</h1>
      {!admin && <button
        onClick={() => window.print()}
        className="nsw-button nsw-button--dark printButton"
      >
        Print
      </button>}
    </div>
    {data.body && <div dangerouslySetInnerHTML={{__html: data.body}} />}
    {admin && <a className="nsw-icon-button" href={'/node/'+id+'/edit'} target="_blank">
      <span className="material-icons nsw-material-icons nsw-material-icons--20" focusable="false" aria-hidden="true">edit</span>
      <span className="sr-only">Edit</span>
    </a>}
  </>
}
