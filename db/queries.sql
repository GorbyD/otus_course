SELECT m.id,
       m.title,
       SUM(t.price) AS total
FROM tickets t
         JOIN sessions s ON s.id = t.session_id
         JOIN movies m ON m.id = s.movie_id
WHERE t.status = 'sold'
GROUP BY m.id, m.title
ORDER BY total DESC LIMIT 1;
