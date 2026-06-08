  fetch('https://xytxyiyan.zeabur.app')
    .then(response => response.json())
    .then(data => {
      const hitokoto = document.querySelector('#hitokoto_text')
      hitokoto.innerText = "『" + data.hitokoto + "』";
      const from = document.querySelector('#hitokoto_from')
      const fromWho = data.from_who ?? '';
      const separator = fromWho ? " " : "";
      from.innerText = fromWho 
        ? `—— ${fromWho}「${data.from}」`
        : `—— 「${data.from}」`;