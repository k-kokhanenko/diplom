import {Header} from '../Header/Header';
import { Modal } from '@material-ui/core';
import { useSelector } from 'react-redux';
import { useState, useRef, useEffect } from 'react';

export const SessionGrid = () => {   
    const [openNewFilm, setOpenNewFilm] = useState(false);
    const [openNewSession, setOpenNewSession] = useState(false);

    const [hallId, setHallId] = useState(0);
    const [filmId, setFilmId] = useState(0);
    const [beginTime, setBeginTime] = useState('00:00');


    const [films, setFilms] = useState([]);
    const [sessions, setSessions] = useState([]);
    const halls = useSelector(state => state.halls);
    
    const inputName = useRef();
    const inputDuration = useRef(); 

    const addNewFilm = async (name, duration) => {
        const response = await fetch(`http://phpsitechecker.ru/films/`, {
          method : "POST",
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ name, duration })
        });
        const data = await response.json();
        if (!data.result) {
            alert(`Ошибка выполнения запроса: ${data.message}`);
        } else {
            setOpenNewFilm(false);
            setFilms(data.films);
        }  
    }

    const deleteFilmFromSession = async (id) => {
        const response = await fetch(`http://phpsitechecker.ru/sessions/${id}/`, {
            method : "DELETE",
          });
        const data = await response.json();
        if (!data.result) {
            alert(`Ошибка выполнения запроса: ${data.message}`);
        } else {
            getSessions();
        }  
    }    

    const addFilmToSession = async () => {        
        const pos = films.findIndex(function(item) { return item.id === filmId; });

        const response = await fetch(`http://phpsitechecker.ru/sessions/`, {
          method : "POST",
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ filmId, hallId : halls[hallId-1].id, beginTime, duration : films[pos].duration })
        });
        const data = await response.json();
        if (!data.result) {
            console.log(`Ошибка выполнения запроса addFilmToSession: ${data.message}`);
            alert(data.message);
        } else {            
            setOpenNewSession(false);
            setSessions(data.sessions);
        }  
    }

    const getFilms = async () => {
        const response = await fetch(`http://phpsitechecker.ru/films/`, {
          method : "GET",
        });
        const data = await response.json();
        if (data.result) {            
            setFilms(data.films);
        }
    }

    const getSessions = async () => {
        const response = await fetch(`http://phpsitechecker.ru/sessions/`, {
          method : "GET",
        });
        const data = await response.json();
        if (data.result) {
            setSessions(data.sessions);            
        } else {
            console.log(`Ошибка выполнения запроса getSessions: ${data.message}`);
        }
    }    

    const handleOnAddFilm = (e) => {
        // Проверяем дилтельность фильма
        if (inputDuration.current.value > 240) {
            alert('Фильм не может быть дилтеьностью больше 4 часов!');
            return;
        }

        addNewFilm(inputName.current.value, inputDuration.current.value);        
    }

    const handleOnSelectFilm = (id) => {
        if (halls !== undefined  && halls.length > 0) {
            setFilmId(id);  
            setOpenNewSession(true);
        } else {
            alert('Добавьте хотябы один кинозал, чтобы добавить фильм в етку сеансов.');
        }
    }

    const handleOnDeleteFilmFromSessions = (id) => {
        deleteFilmFromSession(id);
    }
    
    const getFilmNameById = (id) => {
        const pos = films.findIndex(function(item) { return item.id === id; });
        return films[pos]?.name;
    }

    const showFilmsItems = films?.map(function(film, index) {        
        return <div className="conf-step__movie" key={film.id} onClick={() => handleOnSelectFilm(film.id)} title="Нажмите, чтобы добавить фильм в сетку сеансов">
            <img className="conf-step__movie-poster" alt="poster" src="i/poster.png"/>
            <h3 className="conf-step__movie-title">{film.name}</h3>
            <p className="conf-step__movie-duration">{film.duration} минут</p>
        </div>;
    });     

    const getLeftPosition = (beginTime) => {
        const minute = beginTime.slice(0, 2) * 60 + beginTime.slice(3, 2);
        return minute / 2;
    }
    
    const showHallsItems = halls?.map(function(hall, index) {        
        return <div className="conf-step__seances-hall" key={hall.id}>
            <h3 className="conf-step__seances-title">{hall.name}</h3>
            <div className="conf-step__seances-timeline">
            {sessions?.map((session) =>           
                <>
                    {session.hall_id == hall.id && 
                        <div 
                            className="conf-step__seances-movie"  title={`Нажмите, чтобы удалить фильм '${getFilmNameById(session.films_id)}' из сетки сеансов`}
                            style={{width: `${session.duration/2}px`, backgroundColor: "rgb(133, 255, 137)", left: `${getLeftPosition(session.begin_time)}px`}}
                            onClick={() => handleOnDeleteFilmFromSessions(session.id)}
                        >
                            <p className="conf-step__seances-movie-title">{getFilmNameById(session.films_id)}</p>
                            <p className="conf-step__seances-movie-start">{session.begin_time.slice(0, 5)}</p>
                        </div>
                    }
                </>
            )}              
            </div>
        </div>;
    }); 

    const handleOnAddFilmToSession = () => {

        if (hallId < 1 || !halls[hallId-1]) {            
            alert('Указан некорректный номер зала!');
            return;
        }

        addFilmToSession();
    }

    useEffect(() => {
        getFilms();
        getSessions();
    }, []);    

    return (
        <>    
            <section className="conf-step">
                <Header title='Сетка сеансов' active={false}/>
                <div className="conf-step__wrapper">                
                    <p className="conf-step__paragraph">
                        <button className="conf-step__button conf-step__button-accent" onClick={() => setOpenNewFilm(true)}>Добавить фильм</button>
                    </p>
                    <div className="conf-step__movies">
                        {showFilmsItems}
                    </div>

                    <div className="conf-step__seances">
                        {showHallsItems}
                    </div>
                </div>
                <Modal open={openNewFilm} onClose={() => setOpenNewFilm(false)}>
                    <section className="login">
                    <header className="login__header">
                        <h2 className="login__title">Добавить фильм</h2>
                    </header>
                    <div className="login__wrapper login__form">
                        <label className="login__label" htmlFor="name">
                            Название фильма
                            <input className="login__input" type="text" ref={inputName} placeholder="Укажите название фильма" name="name" required/>
                        </label>
                        <label className="login__label" htmlFor="duration">
                            Длительность (минут)
                            <input className="login__input" type="number" ref={inputDuration} placeholder="Укажите длительность фильма" name="duration" required/>
                        </label>

                        <button className="conf-step__button conf-step__button-accent" onClick={handleOnAddFilm}>Создать</button>
                    </div>
                    </section>
                </Modal>  
                <Modal open={openNewSession} onClose={() => setOpenNewSession(false)}>
                    <section className="login">
                    <header className="login__header">
                        <h2 className="login__title">Добавить фильм в сетку</h2>
                    </header>
                    <div className="login__wrapper login__form">
                        <label className="login__label" htmlFor="hallId">
                            Порядковый номер зала
                            <input className="login__input" type="text" placeholder="Укажите id зала" name="hallId" onChange={(e) =>  setHallId(e.target.value)} required/>
                        </label>
                        <label className="login__label" htmlFor="beginTime">
                            Время начала фильма
                            <input className="login__input" type="time" placeholder="Укажите время начала фильма" name="beginTime" onChange={(e) => setBeginTime(e.target.value)} required/>
                        </label>

                        <button className="conf-step__button conf-step__button-accent" onClick={handleOnAddFilmToSession}>Добавить</button>
                    </div>
                    </section>
                </Modal>  
            </section>
        </>    
    )
}
