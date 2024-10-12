import { useState, useRef } from 'react';
import { Modal } from '@material-ui/core';
import {Header} from '../Header/Header';
import { useSelector, useDispatch } from 'react-redux';

export const Halls = () => {
  const [open, setOpen] = useState(false);

  const halls = useSelector(state => state.halls);
  const dispatch = useDispatch();

  const inputNameRef = useRef();

  const deleteHall = async (id) => {
    const response = await fetch(`http://phpsitechecker.ru/halls/${id}/`, {
      method : "DELETE",
    });
    const data = await response.json();

    if (!data.result) {
      alert(`Ошибка выполнения запроса: ${data.message}`);
    } else {
      dispatch({type: "UPDATE", payload : data.halls});
    }  
  }

  const addNewHall = async (name) => {
    const response = await fetch(`http://phpsitechecker.ru/halls/`, {
      method : "POST",
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ name })
    });
    const data = await response.json();

    if (!data.result) {
      alert(`Ошибка выполнения запроса: ${data.message}`);
    } else {
      dispatch({type: "UPDATE", payload : data.halls});
      setOpen(false);
    }  
  }

  const handleOnDeleteHall = (e) => {    
    const id = e.target.dataset.id;
    deleteHall(id);
  } 

  const handleOnAddHall = (e) => {
    //console.log(`add new hall with name = ${inputNameRef.current.value}`);
    addNewHall(inputNameRef.current.value);
  }

const showHallsItems = halls?.map(function(hall) {
  return <li key={hall.id}>{hall.name} 
  <button className="conf-step__button conf-step__button-trash" data-id={hall.id} onClick={handleOnDeleteHall}></button>
  </li>;
});

  return (
    <>    
    <section className="conf-step">
      <Header title='Управление залами' active={true}/>
      <div className="conf-step__wrapper">
          {halls?.length > 0 && (
            <p className="conf-step__paragraph">Доступные залы:</p>
          )}
          <ul className="conf-step__list">
            {showHallsItems}
          </ul>
          <button className="conf-step__button conf-step__button-accent" onClick={() => setOpen(true)}>Создать зал</button>
          <Modal open={open} onClose={() => setOpen(false)}>
            <section className="login">
              <header className="login__header">
                <h2 className="login__title">Создать зал</h2>
              </header>
              <div className="login__wrapper login__form">
                  <label className="login__label" htmlFor="name">
                    Название 
                    <input className="login__input" ref={inputNameRef} placeholder="Укажите название зала" name="name" required/>
                  </label>
                  <button className="conf-step__button conf-step__button-accent" onClick={handleOnAddHall}>Создать</button>
              </div>
            </section>
          </Modal>       
      </div>      
    </section>
  </>    
  )
}

