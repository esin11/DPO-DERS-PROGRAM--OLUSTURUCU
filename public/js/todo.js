/** todo.js — Yapılacaklar listesi (localStorage kalıcı) */
const TODO_KEY   = 'mdb_todos';
const NOTES_KEY  = 'mdb_notes';

let todos = JSON.parse(localStorage.getItem(TODO_KEY) ?? '[]');

function renderTodos() {
  const col1 = document.getElementById('todo-col-1');
  const col2 = document.getElementById('todo-col-2');
  col1.innerHTML = '';
  col2.innerHTML = '';

  todos.forEach((todo, i) => {
    const div = document.createElement('div');
    div.className = `todo-item${todo.done ? ' done' : ''}`;
    div.innerHTML = `
      <input type="checkbox" ${todo.done ? 'checked' : ''} onchange="toggleTodo(${i})"/>
      <span ondblclick="removeTodo(${i})" title="Çift tıkla: sil">${todo.text}</span>`;
    (i % 2 === 0 ? col1 : col2).appendChild(div);
  });
}

function saveTodos() {
  localStorage.setItem(TODO_KEY, JSON.stringify(todos));
}

window.toggleTodo = function(i) {
  todos[i].done = !todos[i].done;
  saveTodos(); renderTodos();
};

window.removeTodo = function(i) {
  todos.splice(i, 1);
  saveTodos(); renderTodos();
};

document.getElementById('todo-add-btn')?.addEventListener('click', () => {
  const inp = document.getElementById('todo-input');
  const text = inp.value.trim();
  if (!text) return;
  todos.push({ text, done: false });
  inp.value = '';
  saveTodos(); renderTodos();
});

document.getElementById('todo-input')?.addEventListener('keypress', (e) => {
  if (e.key === 'Enter') document.getElementById('todo-add-btn').click();
});

// Notlar
const notesArea = document.getElementById('notes-area');
if (notesArea) {
  notesArea.value = localStorage.getItem(NOTES_KEY) ?? '';
  notesArea.addEventListener('input', () => {
    localStorage.setItem(NOTES_KEY, notesArea.value);
  });
}

renderTodos();
